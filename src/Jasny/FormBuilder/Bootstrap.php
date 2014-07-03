<?php

namespace Jasny\FormBuilder;

use Jasny\FormBuilder;

/**
 * Render element for use with Bootstrap.
 * Optionaly use features from Jasny Bootstrap.
 * 
 * @link http://getbootstrap.com
 * @link http://jasny.github.io/bootstrap
 * 
 * @option int version  Which major Bootstrap version is used
 */
class Bootstrap extends Decorator
{
    /**
     * Prefix for the default fontset
     * @var string
     */
    public static $defaultFontset = 'glyphicon';
    
    
    /**
     * Class constructor
     * 
     * @param array $options
     */
    public function __construct(array $options=[])
    {
        if (!isset($options['version'])) {
            trigger_error("You should specify which version of Bootstrap is used.", E_USER_WARNING);
        } else if ((int)$options['version'] !== 3) {
            throw new \Exception("Only Boostrap version 3 is supported");
        }
    }
    
    /**
     * Wether or not to apply the decorator to all descendants.
     * 
     * @return boolean
     */
    public function isDeep()
    {
        return true;
    }
    
    
    /**
     * Apply modifications
     * 
     * @param Element $element
     */
    public function apply($element)
    {
        $this->applyToElement($element);
        
        $this->applyToAddon('prepend', $element);
        $this->applyToAddon('append', $element);
        $this->applyToLabel($element);
        
        $this->applyToContainer($element);
    }
    
    /**
     * Apply modifications to element
     * 
     * @param Element $element
     */
    protected function applyToElement($element)
    {
        // Add boostrap style class
        if (static::isButton($element)) {
            $style = $element->getOption('btn') ?: 'default';
            $element->addClass('btn' . preg_replace('/^|\s+/', ' btn-', $style));
        } elseif (
            $element instanceof Input && !(in_array($element->getType(), ['checkbox', 'radio'])) ||
            $element instanceof Textarea ||
            $element instanceof Select
        ) {
            $element->addClass('form-control');
        }
    }

    /**
     * Render prepend or append HTML.
     * 
     * @param string  $placement
     * @param Element $element
     * @return string
     */
    protected function applyToAddon($placement, Element $element)
    {
        $fn = 'get' . $placement;
        if (!method_exists($element, $fn)) return;
        
        $addon = $element->$fn();
        
        if (static::isButton($element)) {
            $class = "btn-label" . ($placement === 'append' ? " btn-label-right" : '');
            $addon->addClass(\Closure::bind(function() use ($class) {
                return $this->hasClass('btn-labeled') ? $class : null;
            }, $element));
        } elseif ($element instanceof Input && !static::isButton($element)) {
            $decorator = $this;
            $addon->addClass(\Closure::bind(function() use($decorator, $placement) {
                return $decorator->isButton($this->getOption($placement)) ? 'input-group-btn' : 'input-group-addon';
            }, $element));
        }
    }
    
    /**
     * Apply modifications to label
     * 
     * @param Element $element
     */
    public function applyToLabel(Element $element)
    {
        if (!method_exists($element, 'getLabel')) return;
        if ($element instanceof Input && $element->attr['type'] === 'hidden') return;
        
        $label = $element->getLabel();
        
        $label->addClass(\Closure::bind(function() {
            return $this->getOption('container') ? 'control-label' : null;
        }, $element));
        
        $label->addClass(\Closure::bind(function() {
            $grid = $this->getOption('grid');
            return $grid ? $grid[0] : null;
        }, $element));
    }
    
    /**
     * Apply modifications to container
     * 
     * @param Element $element
     */
    public function applyToContainer(Element $element)
    {
        if (!method_exists($element, 'getContainer')) return;
        
        $container = $element->getContainer();
        
        $container->addClass('form-group');
        
        $container->addClass(\Closure::bind(function() {
            return $this instanceof Control && $this->getError() ? 'has-error' : null;
        }, $element));
    }
    
    
    /**
     * Render the content of the element control to HTML.
     * 
     * @param Element $element
     * @param string  $html     Original rendered html
     * @return string
     */
    public function renderContent(Element $element, $html)
    {
        if (static::isButton($element) && $element->hasClass('btn-labeled')) {
            $prepend = $element->getOption('prepend');
            if ($prepend) $html = $element->getPrepend()->setContent($prepend) . $html;
            
            $append = $element->getOption('append');
            if ($append) $html = $element->getPrepend()->setContent($append) . $html;
        }
        
        return $html;
    }
    
    /**
     * Render the element control to HTML.
     * 
     * @param Element $element
     * @param string  $html     Original rendered html
     * @return string
     */
    public function render(Element $element, $html)
    {
        if (!method_exists($element, 'getContainer')) return $html;
        
        $container = $element->getContainer()->clear();
        
        // Label
        if (method_exists($element, 'getLabel') && $element->getOption('label')) {
            if ($element->getOption('label') !== 'inside') {
                $label = $element->getLabel()->setContent($element->getDescription());
                $label->setAttr('for', $element->getId());
            }
        }
        if (isset($label)) $container->add($label);

        // Grid for horizontal form
        $grid = $element->getOption('grid');
        if ($grid) {
            $div = $container->begin('div')->addClass($grid[1]);
            if (!isset($label)) {
                $div->addClass(preg_replace('/-(\d+)\b/', '-offset-$1', $grid[0]));
            }
        } else {
            $div = $container;
        }
        
        // Add form-control elements
        $this->renderControl($element, $div);
        
        // Validation script
        if (method_exists($element, 'getValidationScript')) {
            $container->add($element->getValidationScript());
        }
        
        return (string)$container;
    }
    
    /**
     * Render form control
     * 
     * @param Element $element
     * @param Group   $container
     */
    protected function renderControl(Element $element, Group $container)
    {
        // Input group for prepend/append
        $useInputGroup = $element instanceof Input && !self::isButton($element) &&
            ($element->getOption('prepend') != '' || $element->getOption('append') != '') &&
            $element->getOption('label') !== 'inside';
        
        $fc = $useInputGroup ? $container->begin('div', [], ['class'=>'input-group']) : $container;
        
        // Prepend
        $labeledButton = static::isButton($element) && $element->hasClass('btn-labeled');
        if (!$labeledButton) {
            $prepend = $element->getOption('prepend');
            if ($prepend) $fc->add($element->getPrepend()->setContent($prepend));
        }
        
        // Element
        $el = $element->renderElement();
        if ($element->getOption('label') === 'inside') {
            $el = $element->getLabel()->setAttr('for', null)->setContent($el);
        }
        $fc->add($el);
        
        // Append
        if (!$labeledButton) {
            $append = $element->getOption('append');
            if ($append) $fc->add($element->getAppend()->setContent($append));
        }
        
        // Help block
        $help = $element->getOption('help');
        if ($help) $container->begin('span', [], ['class'=>'help-block'])->setContent($help);
        
        // Error
        if (method_exists($element, 'getError')) {
            $error = $element->getError();
            if ($error) $element->begin('span', [], ['class'=>'help-block error'])->setContent($error);
        }
    }
    
    
    /**
     * Check if element is a button
     * 
     * @param Element $element
     * @return boolean
     */
    protected static function isButton($element)
    {
        if (!$element instanceof Element) return false;
        
        return
            $element instanceof Button ||
            ($element instanceof Input && in_array($element->attr['type'], ['button', 'submit', 'reset'])) ||
            $element->hasClass('btn') ||
            $element->getOption('btn-style');
    }    
    
    /**
     * Register Boostrap decorator and elements
     * 
     * @return 
     */
    public static function register()
    {
        FormBuilder::$decorators['bootstrap'] = 'Jasny\FormBuilder\Bootstrap';

        FormBuilder::$elements += [
            'bootstrap/fileinput' =>  ['Jasny\FormBuilder\Bootstrap\Fileinput'],
            'bootstrap/imageinput' => ['Jasny\FormBuilder\Bootstrap\Imageinput']
        ];
    }
    
    /**
     * HTML for font icons (like FontAwesome)
     * 
     * @param string $icon     Icon name (and other options)
     * @param string $fontset  Prefix for fonts
     * @return string
     */
    public static function icon($icon, $fontset=null)
    {
        if (!isset($fontset)) $fontset = static::$defaultFontset;
        
        $class = $fontset . preg_replace('/^|\s+/', " $fontset-", $icon);
        return '<i class="' . $class . '"></i>';
    }    
}

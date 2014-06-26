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
     * Apply default modifications.
     * 
     * @param Element $element
     */
    public function apply($element)
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
     * @param string  $html       Original rendered html
     * @return string
     */
    protected function renderAddon($placement, Element $element, $html)
    {
        if (empty($html)) return $html;
        
        if (static::isButton($element) && $element->hasClass('btn-labeled')) {
            $class = "btn-label" . ($placement === 'append' ? ' btn-label-right' : '');
            $html = '<span class="' . $class . '">' . $html . '</span>';
        } elseif ($element instanceof Input && !static::isButton($element)) {
            $class = self::isButton($html) ? 'input-group-btn' : 'input-group-addon';
            $html = '<span class="' . $class . '">' . $html . '</span>';
        }
        
        return $html;
    }
    
    /**
     * Render prepend HTML.
     * 
     * @param Element $element
     * @param string  $html     Original rendered html
     * @return string
     */
    public function renderPrepend(Element $element, $html)
    {
        return $this->renderAddon('prepend', $element, $html);
    }
    
    /**
     * Render append HTML.
     * 
     * @param Element $element
     * @param string  $html     Original rendered html
     * @return string
     */
    public function renderAppend(Element $element, $html)
    {
        return $this->renderAddon('append', $element, $html);
    }
    
    
    /**
     * Render a label bound to the control.
     * 
     * @param Element $element
     * @param string  $html     Original rendered html
     * @return string
     */
    public function renderLabel(Element $element, $html)
    {
        if ($element instanceof Input && $element->attr['type'] === 'hidden') return $html;
        
        $class = $element->getOption('container') ? 'control-label' : '';

        $grid = $element->getOption('grid');
        if ($grid && $element->getOption('container')) {
            $class = ltrim($class . " " . $grid['label']);
        }
        
        $required = $element instanceof Control && $element->getAttr('required') ?
            $element->getOption('required-suffix') : '';
        
        if ($class) $class = 'class="' . $class . '"';
        return "<label {$class} for=\"" . $element->getId() . "\">"
            . $element->getDescription()
            . $required
            . "</label>";
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
        if ($element->hasClass('btn-labeled')) {
            $html = $element->getPrepend() . $html . $element->getAppend();
        }
        
        return $html;
    }
    
    /**
     * Render the element control to HTML.
     * 
     * @param Element $element
     * @param string  $html     Original rendered html
     * @param string  $el       HTML element
     * @return string
     */
    public function renderControl(Element $element, $html, $el)
    {
        if ($element->hasClass('btn-labeled')) $html = $el;
        
        // Input group for prepend/append
        $useInputGroup = $element instanceof Input && !self::isButton($element) &&
            ($element->getOption('prepend') != '' || $element->getOption('append') != '') &&
            $element->getOption('label') !== 'inside';
        
        if ($useInputGroup) $html = '<div class="input-group">' . "\n" . $html . "\n</div>";
        
        // Add help block
        $help = $element->getOption('help');
        if ($help) $html .= "\n<span class=\"help-block\">{$help}</span>";
        
        // Add error
        $error = $element instanceof Control ? $element->getError() : null;
        if ($error) $html .= "\n<span class=\"help-block error\">{$error}</span>";
        
        
        // Grid for horizontal form
        $grid = $element->getOption('grid');
        if ($grid && $element->getOption('container') && !$element instanceof Group) {
            $class = $grid['control'];
            if ($element->getOption('label') !== true) {
                $class .= " " . preg_replace('/-(\d+)\b/', '-offset-$1', $grid['label']);
            }
            $html = '<div class="' . $class . '">' . "\n" . $html . "\n</div>";
        }
        
        return $html;
    }

    /**
     * Render the element container to HTML.
     * 
     * @param Element $element
     * @param string  $html     Original rendered html
     * @param string  $label    HTML of the label
     * @param string  $field    HTML of the control
     * @return string
     */
    public function renderContainer(Element $element, $html, $label, $field)
    {
        if (!$element->getOption('container')) return $html;
        
        $error = $element instanceof Control ? $element->getError() : null;
        $html = ($label ? $label . "\n" : '') . $field;
        
        // Put everything in a container
        if ($element->getOption('container')) {
            $class = "form-group" . ($error ? " has-error" : "");
            $html = "<div class=\"$class\">\n{$html}\n</div>";
        }
        
        return $html;
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
        FormBuilder::$decorators['bootstrap'] = ['Jasny\FormBuilder\Bootstrap'];

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

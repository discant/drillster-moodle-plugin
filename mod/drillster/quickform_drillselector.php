<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
 
require_once('HTML/QuickForm/element.php');

class MoodleQuickForm_drillselector extends HTML_QuickForm_element{
    
    /** @var string html for help button, if empty then no help */
    var $_helpbutton='';
    
    protected $_options = array();
    
    protected $_values = array();

    /**
     * constructor
     *
     * @param string $elementName (optional) name of the text field
     * @param string $elementLabel (optional) text field label
     * @param string $attributes (optional) Either a typical HTML attribute string or an associative array
     */
    function MoodleQuickForm_drillselector($elementName = null, $elementLabel = null, $attributes = null, $options = null) {
            
        global $CFG, $PAGE;
        
        $PAGE->requires->css('/mod/drillster/style/drillselector.css');
  
        $options = (array) $options;
        foreach ($options as $name => $value) {
            if (array_key_exists($name, $this->_options)) {
                $this->_options[$name] = $value;
            }
        }

        $this->_type = 'drillselector';
        
        parent::HTML_QuickForm_element($elementName, $elementLabel, $attributes);
    }

    /**
     * Called by HTML_QuickForm whenever form event is made on this element
     *
     * @param string $event Name of event
     * @param mixed $arg event arguments
     * @param object $caller calling object
     * @return bool
     */
    function onQuickFormEvent($event, $arg, &$caller)
    {
        
        //die($event);
        switch ($event) {
            case 'createElement':
                $caller->setType($arg[0], PARAM_INT);
            break;
            case 'updateValue':

                
            break;
        }
        return parent::onQuickFormEvent($event, $arg, $caller);
    }
    
    /**
    /**
     * Sets name of drillselector
     *
     * @param string $name name of the filemanager
     */
    function setName($name) {
        $this->updateAttributes(array('name'=>$name));
    }

    /**
     * Returns name of drillselector
     *
     * @return string
     */
    function getName() {
        return $this->getAttribute('name');
    }

    /**
     * Updates drillselector attribute value
     *
     * @param string $value value to set
     */
    function setValue($value) {
        $this->updateAttributes(array('value'=>$value));
    }

    /**
     * Returns drillselector attribute value
     *
     * @return string
     */
    function getValue() {  
        return $this->getAttribute('value');
    }

    /**
     * Returns html for help button.
     *
     * @return string html for help button
     */
    function getHelpButton() {
        return $this->_helpbutton;
    }

    /**
     * Returns type of drillselector element
     *
     * @return string
     */
    function getElementTemplateType() {
        if ($this->_flagFrozen){
            return 'nodisplay';
        } else {
            return 'default';
        }
    }
    
    /**
     * Returns HTML for this form element.
     *
     * @return string
     */
    function toHtml(){

        global $CFG, $USER, $COURSE, $PAGE, $OUTPUT;

        if (isguestuser() or !isloggedin()) {
            print_error('noguest');
        }

        if ($this->_flagFrozen) {
            return $this->getFrozenHtml();
        }

        $id          = $this->getAttribute('id');
        $elname      = $this->getAttribute('name');
        $client_id   = uniqid();

        $values      = $this->getValue();

        $options = new stdClass;
        $options->id            = $id;
        $options->elname        = $elname;
        $options->client_id     = $client_id;

        
        $html = $this->_getTabs();
        $ds = new form_drillselector($options);

        $output = $PAGE->get_renderer('mod_drillster');
        $html .= $output->render($ds);

        $html .= html_writer::empty_tag('input', array('value' => $values['drillid'],   'name' => $elname.'[drillid]',  'type' => 'hidden'));
        $html .= html_writer::empty_tag('input', array('value' => $values['view'],      'name' => $elname.'[view]',     'type' => 'hidden'));
        $html .= html_writer::empty_tag('input', array('value' => $values['query'],     'name' => $elname.'[query]',    'type' => 'hidden'));

        // label element needs 'for' attribute work
        $html .= html_writer::empty_tag('input', array('value' => '', 'id' => 'id_'.$elname, 'type' => 'hidden'));

        return $html;
    }
}

/**
 * Data structure representing a file manager.
 *
 * This class defines the data structure for file mnager
 *
 * @package   core_form
 * @copyright 2010 Dongsheng Cai
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @todo      do not use this abstraction (skodak)
 */
class form_drillselector implements renderable {
    /** @var stdClass $options options for filemanager */
    public $options;

    /**
     * Constructor
     */
    public function __construct(stdClass $options) {
        $this->options = $options;
    }
}   
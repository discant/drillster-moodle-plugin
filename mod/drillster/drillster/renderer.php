<?php
defined('MOODLE_INTERNAL') || die();

class mod_drillster_renderer extends plugin_renderer_base {

    public function render_form_drillselector($ds) {

        $this->js_init($ds);

        $html = '';
        $html .= $this->ds_print_generallayout($ds);
        return $html;
    }
    
    public function drillselector_js_templates() {
        $class_methods = get_class_methods($this);
        $templates = array();
        foreach ($class_methods as $method_name) {
            if (preg_match('/^ds_js_template_(.*)$/', $method_name, $matches))
            $templates[$matches[1]] = $this->$method_name();
        }
        return $templates;
    }
    
    public function js_init($ds){
        
        global $PAGE;
        static $filemanagertemplateloaded;
        
        $module = array(
            'name'=>'form_drillselector',
            'fullpath'=>'/mod/drillster/yui/drillster/drillselector.js',
            'requires' => array('moodle-core-notification-dialogue', 'base', 'io-base', 'io-queue','node', 'json', 'template')
        );
        
        if (empty($drillselectortemplateloaded)) {
            $drillselectortemplateloaded = true;
            $this->page->requires->js_init_call('M.form_drillselector.set_templates',
                    array($this->drillselector_js_templates()), true, $module);
        }

        $PAGE->requires->js_init_call('M.form_drillselector.init', array($ds->options), true, $module);
        
    }
    
    public function ds_print_generallayout($ds){
        
        $html = '';
        $html .= html_writer::start_tag('div', array(
            'id' => 'drillselector-'.$ds->options->client_id,
            'class' => 'drillster-drillselector ds-loading'
        ));
        
            $html .= $this->ds_print_header($ds);
            
            $html .= $this->ds_print_body($ds);
        
        $html .= html_writer::end_tag('div');
        
        return $html;
    }

    public function ds_print_header($ds){

            $html = html_writer::start_tag('div', array(
                'class' => 'ds-navbar'
            ));

                $html .= html_writer::start_tag('div', array(
                    'class' => 'ds-toolbar'
                ));
    
                    $html .= html_writer::start_tag('div', array(
                        'class' => 'ds-btn ds-btn-txt ds-btn-myrepertoire'
                    ));
                        
                        $html .= html_writer::tag('a', get_string('btn-my-repertoire', 'mod_drillster'), array(
                            'role' => 'button',
                            'href' => '#'
                        ));

                    $html .= html_writer::end_tag('div').' ';

                    $html .= html_writer::start_tag('div', array(
                        'class' => 'ds-btn ds-btn-txt ds-btn-drillstore'
                    ));

                        $html .= html_writer::tag('a', get_string('btn-drillstore', 'mod_drillster'), array(
                            'role' => 'button',
                            'href' => '#'
                        ));

                    $html .= html_writer::end_tag('div');

                $html .= html_writer::end_tag('div');

                $html .= html_writer::start_tag('div', array(
                    'class' => 'ds-searchbar'
                ));
                    $html .= html_writer::start_tag('div', array(
                        'class' => 'ds-searchfield-container'
                    ));
                        $html .= html_writer::tag('input', '', array(
                            'type' => 'text',
                            'class' => 'ds-searchfield'
                        )).' ';
                        
                        $html .= html_writer::tag('div', 'X', array(
                            'class' => 'ds-btn-searchclear'
                        ));
                        
                    $html .= html_writer::end_tag('div');
                    
                    $html .= html_writer::start_tag('div', array(
                        'class' => 'ds-btn ds-btn-txt ds-btn-search'
                    ));

                        $html .= html_writer::tag('a', get_string('btn-search', 'mod_drillster'), array(
                            'role' => 'button',
                            'href' => '#'
                        ));

                    $html .= html_writer::end_tag('div');
                    
                $html .= html_writer::end_tag('div');

            $html .= html_writer::end_tag('div');



        return $html;
    }

    public function ds_print_body($ds){
        
        $html = html_writer::start_tag('div',
            array(
                'class' => 'ds-drills-container ds-cont-empty'
            )
        );
        
            $html .= html_writer::tag('div', '', array(
                'class' => 'ds-cont-empty'
            ));

        $html .= html_writer::end_tag('div');

        return $html;
    }

    private function ds_js_template_drill() {
        
        $html = html_writer::start_tag('div', array(
            'class' => 'ds-drill'
        ));
            
            $html .= html_writer::tag('div', '<img src="<%= this.icon %>" />', array(
                'class' => 'ds-drill-icon'
            ));

            $html .= html_writer::tag('div', '<%= this.name %>', array(
                'class' => 'ds-drill-name'
            ));
            
            $html .= html_writer::tag('div', '<%= this.description %>', array(
                'class' => 'ds-drill-description'
            ));
            
            $html .= html_writer::tag('div', html_writer::tag('span', '<%= this.subject  %>', array('class' => 'ds-drill-subject')).' | <%= this.size %> entries | Overall <%= this.overall %>%', array(
                'class' => 'ds-drill-property-summary'
            ));
            
            $html .= html_writer::tag('div', html_writer::link('#', get_string('btn-pickdrill', 'mod_drillster')), array(
                'class' => 'ds-btn ds-btn-txt ds-btn-pickdrill ds-btn-enabled'
            ));
            
            $html .= html_writer::tag('div', html_writer::link('#', get_string('btn-removedrill', 'mod_drillster')), array(
                'class' => 'ds-btn ds-btn-txt ds-btn-removedrill ds-btn-enabled'
            ));
            
        $html .= html_writer::end_tag('div');
        
        return $html;
    } 
    
    private function ds_js_template_emptystore(){
        
       global $CFG;
        
       $html = html_writer::start_tag('div', array(
            'class' => 'ds-emptystore-container'
        ));
            
            $html .= html_writer::tag('div', get_string('no_search_query','mod_drillster'), array(
                'class' => 'ds-emptystore-name'
            ));
            
            $html .= html_writer::empty_tag('img', array(
                'class' => 'ds-emptystore-search',
                'src' => $CFG->wwwroot.'/mod/drillster/pix/arrow.png'
            ));
            
        $html .= html_writer::end_tag('div');
        
        return $html;
    }

    private function ds_js_template_viewloading(){
        
        $html = html_writer::start_tag('div', array(
            'class' => 'ds-drills-container-loading',
            'style' => 'background: url("'.$this->pix_url('i/loading_small').'") no-repeat center center;'));
        $html .= html_writer::end_tag('div');
        
        return $html;
    }

    private function ds_js_template_message(){
        
        $html = html_writer::start_tag('div', array(
            'class' => 'ds-msg',
            'role' => 'alertdialog',
            'aria-live' => 'assertive',
            'aria-labelledbt' => 'ds-msg-labelledby'));
            
            $html .= html_writer::start_tag('p' , array(
                'class' => 'ds-msg-text',
                'id' => 'ds-msg-labelledby'));
              
            $html .= html_writer::end_tag('p');
                
            $html .= html_writer::tag('button' , get_string('ok'), array(
                'class' => 'ds-msg-butok btn-primary btn',
                'id' => 'ds-msg-labelledby'));
                    
        $html .= html_writer::end_tag('div');
        
        return $html;
    }
    
    private function ds_js_template_noresults(){
        
       global $CFG;
        
       $html = html_writer::start_tag('div', array(
            'class' => 'ds-noresults-container'
        ));
            
            $html .= html_writer::tag('div', get_string('no_results','mod_drillster'), array(
                'class' => 'ds-noresults-title'
            ));
            
        $html .= html_writer::end_tag('div');
        
        return $html;
    }
    
    private function ds_js_template_tryagain(){
        
       global $CFG;
        
       $html = html_writer::start_tag('div', array(
            'class' => 'ds-noresults-container'
        ));
            
            $html .= html_writer::tag('div', get_string('try_again','mod_drillster'), array(
                'class' => 'ds-noresults-title'
            ));
            
        $html .= html_writer::end_tag('div');
        
        return $html;
    }
}


















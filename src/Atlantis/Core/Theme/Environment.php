<?php namespace Atlantis\Core\Theme;


class Environment {
    protected $config;
    protected $files;
    protected $assets = [];
    protected $themes_base_path;

    protected $theme_prefixes = [];
    protected $theme_attributes = [];
    protected $theme_stylesheets = [];
    protected $theme_javascripts = [];

    protected $app_locale;

    const PREFIX_PATTERN = '/\w+[!]/';

    protected $theme_rules = array(
        'info'  => 'required|array',
        'register' => 'array',
        'assets' => 'array'
    );


    public function __construct($config,$files,$assets){
        $this->config = $config;
        $this->files = $files;
        $this->assets = $assets;

        $this->themes_base_path = $this->config->get('core::app.theme.base_path');

        $this->locale = $config->get('app.locale');
    }


    public function load($theme=null){
        #i: Get default theme if not set
        if(empty($theme)) {
            $theme = $this->config->get('core::app.theme.default');
        }

        #i: Construct theme config file
        $theme_config_path = "{$this->themes_base_path}/$theme/config.php";

        #i: Check theme with config file exist
        if( !$this->files->exists($theme_config_path) ) return null;

        #i: Add theme to config path, for later flexible access
        $this->config->addNamespace("themes/$theme","{$this->themes_base_path}/$theme/");

        #i: Fetch theme configs
        $configs = $this->config->get('themes/default::config');

        #i: Validate theme config structure
        // Later !!

        #i: Load prefixes
        if( isset($configs['register']['prefixes']) ) $this->theme_prefixes = $configs['register']['prefixes'];

        #i: Load attributes
        if( isset($configs['register']['attributes']) ) $this->theme_attributes = $configs['register']['attributes'];

        #i: Register stylesheet to load
        if( isset($configs['assets']['stylesheet']) ) $this->registerStylesheet($configs['assets']['stylesheet']);

        #i: Register javascript to load
        if( isset($configs['assets']['javascript']) ) $this->registerJavascript($configs['assets']['javascript']);

        #i: Boot stylesheet with asset provider
        $this->bootStylesheet($this->theme_stylesheets);

        #i: Boot javascript with asset provider
        $this->bootJavascript($this->theme_javascripts);

        return true;
    }


    public function registerStylesheet($assets_css=[],$theme=null){
        #i: Get default theme name if not supplied
        if(empty($theme)) $theme =  $this->config->get('core::app.theme.default');

        foreach($assets_css as $css){
            #i: Check if value contain array
            if( is_array($css) ){
                $this->registerStylesheet($css);
                continue;
            }

            #i: Apply prefix
            $css = $this->applyPrefix($css);

            if($this->files->isFile($css)){
                #i: Add to stylesheet collection
                $this->theme_stylesheets[] = $css;

            }elseif($this->files->isDirectory($css)){
                foreach( glob("$css/*.{css,less}",GLOB_BRACE) as $file_path ){
                    $this->theme_stylesheets[] = $file_path;
                }

            }else{
                #i: Guess if css path if it in components
                //$css_path = $this->config->get('core::app.component.base_path') . "/$css";
                $css_path = "{$this->themes_base_path}/$theme/assets/$css";

                #i: If css file exist then add
                if( $this->files->isFile($css_path) ) $this->theme_stylesheets[] = $css_path;
            }
        }
    }


    public function bootStylesheet($stylesheets=[],$group='common'){
        $this->assets->collection($group,function($collection) use($stylesheets){
            foreach($stylesheets as $stylesheet){
                $file_extension = $this->files->extension($stylesheet);

                if( $file_extension == 'css' ){
                    $collection->stylesheet($stylesheet);
                }else{
                    $collection->stylesheet($stylesheet)->apply(studly_case($file_extension));
                }
            }
        })->apply('CssMin')
            ->andApply('UriRewriteFilter')
            ->andApply('UriPrependFilter')
            ->setArguments($this->config->get('app.url'));
    }


    public function registerJavascript($assets_js=[],$theme=null){
        #i: Get default theme name if not supplied
        if(empty($theme)) $theme =  $this->config->get('core::app.theme.default');

        #i: Process all javascripts
        foreach($assets_js as $js){
            #i: Check if value contain array
            if( is_array($js) ){
                $this->registerJavascript($js);
                continue;
            }

            #i: Apply prefix
            $js = $this->applyPrefix($js);

            if($this->files->isFile($js)){
                #i: Add to stylesheet collection
                $this->theme_javascripts[] = $js;

            }elseif($this->files->isDirectory($js)){
                foreach( glob("$js/*.{js}",GLOB_BRACE) as $file_path ){
                    $this->theme_javascripts[] = $file_path;
                }

            }else{
                #i: Guess if css path if it in components
                //$js_path = $this->config->get('core::app.component.base_path') . "/$js";
                $js_path = "{$this->themes_base_path}/$theme/assets/$js";

                #i: If css file exist then add
                if( $this->files->isFile($js_path) ) $this->theme_javascripts[] = $js_path;
            }
        }
    }


    public function bootJavascript($javascripts=[],$group='common'){
        $this->assets->collection($group,function($collection) use($javascripts){
            foreach($javascripts as $javascript){
                $file_extension = $this->files->extension($javascript);

                if( $file_extension == 'js' ){
                    $collection->javascript($javascript);
                }else{
                    $collection->javascript($javascript)->apply(studly_case($file_extension));
                }
            }
        })->apply('JsMin');
    }


    public function applyPrefix($value){
        $prefixes = $this->theme_prefixes;

        #i: Find all prefix in value string
        preg_match_all('/\w+[!]/',$value,$matches);

        #i: Replace all matched with prefix value
        array_walk($matches[0], function($item) use($prefixes,&$value){
            if( isset($prefixes[rtrim($item,'!')]) ) $value = str_replace($item,$prefixes[rtrim($item,'!')],$value);
        });

        return $value;
    }
}
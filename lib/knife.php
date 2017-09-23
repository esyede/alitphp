<?php
/**
*   Tiny Blade-Like Template Engine for Alit PHP
*   @package     Alit PHP
*   @subpackage  Alit.Knife
*   @copyright   Copyright (c) 2017-2011 Suyadi. All Rights Reserved.
*   @license     https://opensource.org/licenses/MIT The MIT License (MIT)
*   @author      Suyadi <suyadi.1992@gmail.com>
*/


class Knife extends \Preview {
    protected
        $base,
        $cache,
        $format,
        $base_url,
        $type=['command','comment','echo'];


    // Class constructor
    function __construct() {
        $fw=Alit::instance();
        $this->base=$fw->grab('BASE');
        $this->base_url=$fw->grab('HOST').$fw->grab('URI');
        $cache=$this->base.str_replace('./','',$fw->grab('TEMP'));
        $cache=str_replace('/',DIRECTORY_SEPARATOR,$cache);
        $this->cache=$cache;
        $this->format="htmlspecialchars(%s,ENT_QUOTES,'UTF-8')";
        parent::__construct();
    }

    /**
    *   Add file to include
    *   @param   $name   string
    *   @return  string
    */
    protected function tpl($name) {
        $tpl=$this->ui.str_replace('/',DIRECTORY_SEPARATOR,$name.'.knife.php');
        $php=$this->cache.DIRECTORY_SEPARATOR.md5($name).'.knife.php';
        if (!file_exists($php)||filemtime($tpl)>filemtime($php)) {
            $text=str_replace('@BASE','<?php echo $this->base_url?>',file_get_contents($tpl));
            foreach ($this->type as $type)
                $text=$this->{'_'.$type}($text);
            file_put_contents($php,$text);
        }
        return $php;
    }

    /**
    *   Compile statements that start with "@"
    *   @param   $vars  string
    *   @return  mixed
    */
    protected function _command($vars) {
        return preg_replace_callback('/\B@(\w+)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x',function ($match) {
            if (method_exists($this,$method='_'.ucfirst($match[1])))
                $match[0]=$this->$method(isset($match[3])?$match[3]:'');
            return isset($match[3])?$match[0]:$match[0].$match[2];
        },$vars);
    }

    /**
    *   Compile comments
    *   @param   $vars   string
    *   @return  string
    */
    protected function _comment($vars) {
        return preg_replace('/\{\{--((.|\s)*?)--\}\}/','<?php /*$1*/?>',$vars);
    }

    /**
    *   Compile echos
    *   @param   $vars   string
    *   @return  string
    */
    protected function _echo($vars) {
        $vars=preg_replace_callback('/\{\{\{\s*(.+?)\s*\}\}\}(\r?\n)?/s',function ($found) {
            $space=empty($found[2])?'':$found[2];
            return '<?php echo htmlspecialchars('.
                $this->_echodefault($found[1]).",ENT_QUOTES,'UTF-8')?>".$space;
        },$vars);
        $vars=preg_replace_callback('/\{\!!\s*(.+?)\s*!!\}(\r?\n)?/s',function ($found) {
            $space=empty($found[2])?'':$found[2];
            return '<?php echo '.$this->_echodefault($found[1]).'?>'.$space;
        },$vars);
        $vars=preg_replace_callback('/(@)?\{\{\s*(.+?)\s*\}\}(\r?\n)?/s',function ($found) {
            $space=empty($found[3])?'':$found[3];
            return $found[1]?substr($found[0],1):'<?php echo '.
                sprintf($this->format,$this->_echodefault($found[2])).'?>'.$space;
        },$vars);
        return $vars;
    }

    /**
    *   Change echo format
    *   @param  $format  string
    */
    protected function format($format) {
        $this->format=$format;
    }

    /**
    *   Compile the default values for the echo statement.
    *   @param   $vars   string
    *   @return  string
    */
    function _echodefault($vars) {
        return preg_replace('/^(?=\$)(.+?)(?:\s+or\s+)(.+?)$/s','isset($1)?$1:$2',$vars);
    }

    /**
    *   Compile the @if statements
    *   @param   $expr    string
    *   @return  string
    */
    protected function _if($expr) {
        return "<?php if {$expr}:?>";
    }

    /**
    *   Compile the @elseif statements
    *   @param   $expr    string
    *   @return  string
    */
    protected function _elseif($expr) {
        return "<?php elseif {$expr}:?>";
    }

    /**
    *   Compile the @endif statements
    *   @param   $expr    string
    *   @return  string
    */
    protected function _endif($expr) {
        return "<?php endif;?>";
    }

    /**
    *   Compile the @unless statements
    *   @param   $expr    string
    *   @return  string
    */
    protected function _unless($expr) {
        return "<?php if (!$expr):?>";
    }

    /**
    *   Compile the @endunless statements
    *   @param   $expr    string
    *   @return  string
    */
    protected function _endunless($expr) {
        return "<?php endif;?>";
    }

    /**
    *   Compile the @for statements
    *   @param   $expr    string
    *   @return  string
    */
    protected function _for($expr) {
        return "<?php for {$expr}:?>";
    }

    /**
    *   Compile the @endfor statements
    *   @param   $expr    string
    *   @return  string
    */
    protected function _endfor($expr) {
        return "<?php endfor;?>";
    }

    /**
    *   Compile the @foreach statements
    *   @param   $expr    string
    *   @return  string
    */
    protected function _foreach($expr) {
        return "<?php foreach {$expr}:?>";
    }

    /**
    *   Compile the @endforeach statements
    *   @param   $expr    string
    *   @return  string
    */
    protected function _endforeach($expr) {
        return "<?php endforeach;?>";
    }

    /**
    *   Compile the @while statements
    *   @param   $expr    string
    *   @return  string
    */
    protected function _while($expr) {
        return "<?php while {$expr}:?>";
    }

    /**
    *   Compile the @endwhile statements
    *   @param   $expr    string
    *   @return  string
    */
    protected function _endwhile($expr) {
        return "<?php endwhile;?>";
    }

    /**
    *   Compile the @extends statements
    *   @param   $expr    string
    *   @return  string
    */
    protected function _extends($expr) {
        if (isset($expr{0})&&$expr{0}=='(')
            $expr=substr($expr,1,-1);
        return "<?php \$this->extend({$expr})?>";
    }

    /**
    *   Compile the @include statements
    *   @param   $expr    string
    *   @return  string
    */
    protected function _include($expr) {
        if (isset($expr{0})&&$expr{0}=='(')
            $expr=substr($expr,1,-1);
        return "<?php include \$this->tpl({$expr})?>";
    }

    /**
    *   Compile the @yield statements (PHP 5.5+)
    *   @param   $expr    string
    *   @return  string
    */
    protected function _yield($expr) {
        return "<?php echo \$this->block{$expr}?>";
    }

    /**
    *   Compile the @section statements
    *   @param   $expr    string
    *   @return  string
    */
    protected function _section($expr) {
        return "<?php \$this->beginblock{$expr}?>";
    }

    /**
    *   Compile the @endsection statements
    *   @param   $expr    string
    *   @return  string
    */
    protected function _endsection($expr) {
        return "<?php \$this->endblock()?>";
    }

    /**
    *   Compile the @show statements
    *   @param   $expr    string
    *   @return  string
    */
    protected function _show($expr) {
        return "<?php echo \$this->block(\$this->endblock())?>";
    }

    /**
    *   Compile the @append statements
    *   @param   $expr    string
    *   @return  string
    */
    protected function _append($expr) {
        return "<?php \$this->endblock()?>";
    }

    /**
    *   Compile the @stop statements
    *   @param   $expr    string
    *   @return  string
    */
    protected function _stop($expr) {
        return "<?php \$this->endblock()?>";
    }

    /**
    *   Compile the @overwrite statements
    *   @param   $expr    string
    *   @return  string
    */
    protected function _overwrite($expr) {
        return "<?php \$this->endblock(true)?>";
    }
}
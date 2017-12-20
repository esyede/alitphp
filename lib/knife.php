<?php
/**
 * Tiny Template Library for Alit PHP
 * @package     Alit
 * @subpackage  Knife
 * @copyright   Copyright (c) 2017 Suyadi. All Rights Reserved.
 * @license     <https://opensource.org/licenses/MIT> The MIT License (MIT).
 * @author      Suyadi <suyadi.1992@gmail.com>
 */
// Prohibit direct access to file
defined('DS') or die('Direct file access is not allowed.');



class Knife extends \Preview {

    protected
        // Output format
        $format,
        // Path to save chache file
        $cachepath;

    const
        // Command-type tokens
        TOKENS='command|comment|echo';
    
    /**
     * Class constructor
     */
    function __construct() {
        parent::__construct();
        $fw=\Alit::instance();
        $this->cachepath($fw->get('ROOT').str_replace('./','',$fw->get('TEMP')));
        $this->format("htmlspecialchars(%s,ENT_QUOTES,'UTF-8')");
    }

    /**
     * Change echo format
     * @param   string $format
     * @return  void
     */
    protected function format($format) {
        $this->format=$format;
    }

    /**
     * Change cache location
     * @param   string  $path
     * @return  void
     */
    protected function cachepath($path) {
        $this->cachepath=$path;
    }

    /**
     * Clean up cache
     * @return boolean
     */
    function cleanup() {
        foreach (glob($this->cachepath.'*.knife.php') as $f)
			if (unlink($f))
                return TRUE;
        return FALSE;
    }

    /**
     * Add a file to include
     * @param   string  $name
     * @return  string
     */
    protected function tpl($name) {
        $fw=\Alit::instance();
        $tpl=preg_replace('/\s\s+/','',$this->ui.$name.'.knife.php');
        $php=$this->cachepath.DS.md5($name).'.knife.php';
        if (!file_exists($php)||filemtime($tpl)>filemtime($php)) {
            $txt=preg_replace('/@BASE/',$fw->base(),$fw->read($tpl));
            foreach ($fw->split(self::TOKENS) as $type)
                $txt=$this->{'_'.$type}($txt);
            $fw->write($php,$txt);
        }
        return $php;
    }

    /**
     * Compile statement starts with '@'
     * @param   string  $vars
     * @return  mixed
     */
    protected function _command($vars) {
        return preg_replace_callback('/\B@(\w+)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x',function($match) {
            if (method_exists($this,$method='_'.strtolower($match[1])))
                $match[0]=$this->$method(isset($match[3])?$match[3]:'');
            return isset($match[3])?$match[0]:$match[0].$match[2];
        },$vars);
    }

    /**
     * Compile comments
     * @param   string  $vars
     * @return  string
     */
    protected function _comment($vars) {
        return preg_replace('/\{\{--((.|\s)*?)--\}\}/','<?php /*$1*/?>',$vars);
    }

    /**
     * Compile echos
     * @param   string  $vars
     * @return  string
     */
    protected function _echo($vars) {
        $vars=preg_replace_callback('/\{\{\{\s*(.+?)\s*\}\}\}(\r?\n)?/s',function($found) {
            $space=empty($found[2])?'':$found[2];
            return '<?php echo htmlspecialchars('.
                $this->_echodefault($found[1]).",ENT_QUOTES,'UTF-8')?>".$space;
        },$vars);
        $vars=preg_replace_callback('/\{\!!\s*(.+?)\s*!!\}(\r?\n)?/s',function($found) {
            $space=empty($found[2])?'':$found[2];
            return '<?php echo '.$this->_echodefault($found[1]).'?>'.$space;
        },$vars);
        $vars=preg_replace_callback('/(@)?\{\{\s*(.+?)\s*\}\}(\r?\n)?/s',function($found) {
            $space=empty($found[3])?'':$found[3];
            return $found[1]?substr($found[0],1):'<?php echo '.
                sprintf($this->format,$this->_echodefault($found[2])).'?>'.$space;
        },$vars);
        return $vars;
    }

    /**
     * Compile default value for rhe echo
     * @param   string  $vars
     * @return  string
     */
    function _echodefault($vars) {
        return preg_replace('/^(?=\$)(.+?)(?:\s+or\s+)(.+?)$/s','isset($1)?$1:$2',$vars);
    }

    /**
     * Compile @if statement
     * @param   string  $expr
     * @return  string
     */
    protected function _if($expr) {
        return "<?php if {$expr}:?>";
    }

    /**
     * Compile @elseif statement
     * @param   string  $expr
     * @return  string
     */
    protected function _elseif($expr) {
        return "<?php elseif {$expr}:?>";
    }

    /**
     * Compile @endif statement
     * @param   string  $expr
     * @return  string
     */
    protected function _endif($expr) {
        return "<?php endif;?>";
    }

    /**
     * Compile @unless statement
     * @param   string  $expr
     * @return  string
     */
    protected function _unless($expr) {
        return "<?php if (!$expr):?>";
    }

    /**
     * Compile @endunless statement
     * @param   string  $expr
     * @return  string
     */
    protected function _endunless($expr) {
        return "<?php endif;?>";
    }

    /**
     * Compile @for statement
     * @param   string  $expr
     * @return  string
     */
    protected function _for($expr) {
        return "<?php for {$expr}:?>";
    }

    /**
     * Compile @endfor statement
     * @param   string  $expr
     * @return  string
     */
    protected function _endfor($expr) {
        return "<?php endfor;?>";
    }

    /**
     * Compile @foreach statement
     * @param   string  $expr
     * @return  string
     */
    protected function _foreach($expr) {
        return "<?php foreach {$expr}:?>";
    }

    /**
     * Compile @endforeach statement
     * @param   string  $expr
     * @return  string
     */
    protected function _endforeach($expr) {
        return "<?php endforeach;?>";
    }

    /**
     * Compile @while statement
     * @param   string  $expr
     * @return  string
     */
    protected function _while($expr) {
        return "<?php while {$expr}:?>";
    }

    /**
     * Compile @endwhile statement
     * @param   string  $expr
     * @return  string
     */
    protected function _endwhile($expr) {
        return "<?php endwhile;?>";
    }

    /**
     * Compile @extends statement
     * @param   string  $expr
     * @return  string
     */
    protected function _extends($expr) {
        if (isset($expr{0})&&$expr{0}=='(')
            $expr=substr($expr,1,-1);
        return "<?php \$this->extend({$expr})?>";
    }

    /**
     * Compile @include statement
     * @param   string  $expr
     * @return  string
     */
    protected function _include($expr) {
        if (isset($expr{0})&&$expr{0}=='(')
            $expr=substr($expr,1,-1);
        return "<?php include \$this->tpl({$expr})?>";
    }

    /**
     * Compile @yield statement
     * @param   string  $expr
     * @return  string
     */
    protected function _yield($expr) {
        return "<?php echo \$this->block{$expr}?>";
    }

    /**
     * Compile @section statement
     * @param   string  $expr
     * @return  string
     */
    protected function _section($expr) {
        return "<?php \$this->beginblock{$expr}?>";
    }

    /**
     * Compile @endsection statement
     * @param   string  $expr
     * @return  string
     */
    protected function _endsection($expr) {
        return "<?php \$this->endblock()?>";
    }

    /**
     * Compile @show statement
     * @param   string  $expr
     * @return  string
     */
    protected function _show($expr) {
        return "<?php echo \$this->block(\$this->endblock())?>";
    }

    /**
     * Compile @append statement
     * @param   string  $expr
     * @return  string
     */
    protected function _append($expr) {
        return "<?php \$this->endblock()?>";
    }

    /**
     * Compile @stop statement
     * @param   string  $expr
     * @return  string
     */
    protected function _stop($expr) {
        return "<?php \$this->endblock()?>";
    }

    /**
     * Compile @overwrite statement
     * @param   string  $expr
     * @return  string
     */
    protected function _overwrite($expr) {
        return "<?php \$this->endblock(TRUE)?>";
    }
}

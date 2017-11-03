<?php
/**
*   Tiny Unit-testing tool for Alit PHP
*   @package     Alit PHP
*   @subpackage  Alit.Test
*   @copyright   Copyright (c) 2017-2011 Suyadi. All Rights Reserved.
*   @license     https://opensource.org/licenses/MIT The MIT License (MIT)
*   @author      Suyadi <suyadi.1992@gmail.com>
*/
// Prohibit direct access to file
if (!defined('DS')) die('Direct file access is not allowed.');



class Test {

	const
		// Reporting level
		RL_FALSE=0,
		RL_TRUE=1,
		RL_BOTH=2;

	protected
		// Test results
		$data=[],
		// Success indicator
		$passed=true;

	/**
	*	Return test results
	*	@return  array
	*/
	function results() {
		return $this->data;
	}

	/**
	*	Return FALSE if at least one test case fails
	*	@return  bool
	*/
	function passed() {
		return $this->passed;
	}

	/**
	*	Evaluate condition and save test result
	*	@param   $cond    bool
	*	@param   $msg     string
    *	@return  object
	*/
	function expect($cond,$msg=null) {
		$out=(bool)$cond;
		if ($this->level==$out
        ||$this->level==self::RL_BOTH) {
			$data=['status'=>$out,'message'=>$msg,'source'=>null];
			foreach (debug_backtrace() as $src)
				if (isset($src['file'])) {
					$data['source']=\Alit::instance()->slash($src['file']).
						':<font color="red">'.$src['line'].'</font>';
					break;
				}
			$this->data[]=$data;
		}
		if (!$out&&$this->passed)
			$this->passed=false;
		return $this;
	}

	/**
	*	Append message to test results
	*	@param   $msg  string
    *	@return  null
	*/
	function message($msg) {
		$this->expect(true,$msg);
	}

	/**
	*	Class constructor
	*	@param   $level  int
    *	@return  null
	*/
	function __construct($level=self::RL_BOTH) {
		$this->level=$level;
	}

}

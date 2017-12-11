<?php
/**
*   Tiny Unit-testing Tool for Alit PHP
*   @package     Alit PHP
*   @subpackage  Alit.Test
*   @copyright   Copyright (c) 2017 Suyadi. All Rights Reserved.
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
		$passed=TRUE;

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
	function expect($cond,$msg=NULL) {
		$fw=\Alit::instance();
		$out=(bool)$cond;
		if ($this->level==$out
        ||$this->level==self::RL_BOTH) {
			$data=['status'=>$out,'message'=>$msg,'source'=>NULL];
			foreach (debug_backtrace() as $src)
				if (isset($src['file'])) {
					$data['source']=$fw->slash($src['file']).
						':<font color="red">'.$src['line'].'</font>';
					break;
				}
			$this->data[]=$data;
		}
		if (!$out&&$this->passed)
			$this->passed=FALSE;
		return $this;
	}

	/**
	*	Append message to test results
	*	@param   $msg  string
    *	@return  NULL
	*/
	function message($msg) {
		$this->expect(TRUE,$msg);
	}

	/**
	*	Class constructor
	*	@param   $level  int
    *	@return  NULL
	*/
	function __construct($level=self::RL_BOTH) {
		$this->level=$level;
	}
}

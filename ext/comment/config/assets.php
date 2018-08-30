<?php
/****************************************************************************
* todoyu is published under the BSD License:
* http://www.opensource.org/licenses/bsd-license.php
*
* Copyright (c) 2012, snowflake productions GmbH, Switzerland
* All rights reserved.
*
* This script is part of the todoyu project.
* The todoyu project is free software; you can redistribute it and/or modify
* it under the terms of the BSD License.
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the BSD License
* for more details.
*
* This copyright notice MUST APPEAR in all copies of the script.
*****************************************************************************/

/**
 * Assets (JS, CSS, SWF, etc.) requirements for comment extension
 *
 * @package		Todoyu
 * @subpackage	Comment
 */
Todoyu::$CONFIG['EXT']['comment']['assets'] = array(
	'js' => array(
		array(
			'file'		=> 'ext/comment/asset/js/Ext.js',
			'position'	=> 100
		),
		array(
			'file'		=> 'ext/comment/asset/js/List.js',
			'position'	=> 101
		),
		array(
			'file'		=> 'ext/comment/asset/js/Comment.js',
			'position'	=> 101
		),
		array(
			'file'		=> 'ext/comment/asset/js/Edit.js',
			'position'	=> 101
		)
	),
	'css' => array(
		array(
			'file'		=> 'ext/comment/asset/css/ext.scss',
			'position'	=> 100
		)
	)
);

?>
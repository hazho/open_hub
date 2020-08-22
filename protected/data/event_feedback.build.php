<?php

return array(
	'layout' => 'layouts.backend',
	'moduleCode' => 'event',
	'isAllowMeta' => true,
	'foreignRefer' => array('key' => 'id', 'title' => 'rating'),
	'menuTemplate' => array(
		'index' => 'admin, create',
		'admin' => 'create',
		'create' => 'admin',
		'update' => 'admin, create, view',
		'view' => 'admin, create, update, delete',
	),
	'admin' => array(
		'list' => array('id', 'email', 'event_code', 'rating'),
		'sortDefaultOrder' => 't.id DESC',
	),
	'structure' => array(
	),
	'foreignKey' => array(
		'event_code' => array('relationName' => 'event', 'model' => 'Event', 'foreignReferAttribute' => 'title'),
		'email' => array('relationName' => 'user', 'model' => 'User', 'foreignReferAttribute' => 'username'),
	),
);

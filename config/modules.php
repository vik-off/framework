<?

return array(

	'admin' => array(
		'controller' => 'Admin_Controller',
	),
	
	'page' => array(
		'title' => 'Страницы',
		'controller' => 'Page_Controller',
		'adminController' => 'Page_AdminController',
		'resources' => array(
			'view' => 'Просмотр страниц',
			'edit' => 'Редактирование страниц',
			'root' => 'Root-привилегии (установка ограничений, запрет удаления)',
		)
	),
	
	'user' => array(
		'controller' => 'User_Controller',
		'adminController' => 'User_AdminController',
	),


);

?>
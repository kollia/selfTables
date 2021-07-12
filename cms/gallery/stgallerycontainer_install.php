<?php


		require_once($_stdbtabledescriptions);
		
		$_stgallerycontainer_table_description= &STDbTableDescriptions::instance();
		$_stgallerycontainer_table_description->table("gallery");
		$_stgallerycontainer_table_description->column("gallery", "ID", "int", false);
		$_stgallerycontainer_table_description->primaryKey("gallery", "ID");
		$_stgallerycontainer_table_description->autoIncrement("gallery", "ID");
		$_stgallerycontainer_table_description->column("gallery", "suborder", "varchar(70)", false);
		$_stgallerycontainer_table_description->column("gallery", "deep", "int", false);
		$_stgallerycontainer_table_description->column("gallery", "type", "enum('order','gallery', 'image')");
		$_stgallerycontainer_table_description->column("gallery", "download", "enum('No','Yes')");
		$_stgallerycontainer_table_description->column("gallery", "userText", "varchar(255)");
		$_stgallerycontainer_table_description->column("gallery", "tumpnail", "varchar(255)");
		$_stgallerycontainer_table_description->column("gallery", "showen", "varchar(255)");
		$_stgallerycontainer_table_description->column("gallery", "picture", "varchar(255)");
		$_stgallerycontainer_table_description->column("gallery", "before", "text");
		$_stgallerycontainer_table_description->column("gallery", "description", "text");
		$_stgallerycontainer_table_description->column("gallery", "behind", "text");
		$_stgallerycontainer_table_description->column("gallery", "parent", "int", true);
		$_stgallerycontainer_table_description->foreignKey("gallery", "parent", "gallery");
		$_stgallerycontainer_table_description->column("gallery", "uploadPath", "varchar(255)");
		$_stgallerycontainer_table_description->column("gallery", "ftpPath", "varchar(255)");		
		$_stgallerycontainer_table_description->column("gallery", "projectID", "int", false);
		$_stgallerycontainer_table_description->column("gallery", "sort", "int", false);
		$_stgallerycontainer_table_description->column("gallery", "sortDirection", "set('ASC', 'DESC')", false);
		$_stgallerycontainer_table_description->column("gallery", "active", "set('No','Yes')", false);
		$_stgallerycontainer_table_description->column("gallery", "from_date", "date", false);
		
		
?>
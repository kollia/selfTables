<?php

require_once($_stdbdefinserter);

class STSubGalleryContainer extends STObjectContainer
{
	//var	$sGalleryTable= "gallery";
	var $bAdmin= true;
	var $oOrderContainer= null;
	var $oPicContainer= null;
	var $oGalleryTable;
	var	$asTableColumns= array();
	var $documentRoot;
	var $bPhpUpload= true;
	var	$uploadPath;
	var $ftpPath;
	var $sDisplayOrder= "";
	var $nFirstDeep= 0;
	var	$anPicSize;
	
	function __construct(&$container, $name= "gallery")
	{
		STObjectContainer::STObjectContainer($name, $container);
		$this->documentRoot= $_SERVER["DOCUMENT_ROOT"];
		
		$this->anPicSize= array("showen"  => 500,
								"tumpnail"=> 200   );
		
	}
	function uploadPath($path)
	{
		Tag::alert(!$this->existsPath($path), "STSubGalleryContainer::fistSavePath()", "uploadPath $path does not exist");
		
		$path= trim($path);
		if(substr($path, strlen($path)-1)!="/")
			$path.= "/";
		$this->uploadPath= $path;
		
		if(!$this->ftpPath)
			$this->ftpPath= $path;
	}
	function ftpPath($path)
	{
		Tag::alert(!$this->existsPath($path), "STSubGalleryContainer::fistSavePath()", "ftpPath $path does not exist");
		
		$path= trim($path);
		if(substr($path, strlen($path)-1)!="/")
			$path.= "/";
		$this->ftpPath= $path;
	}
	function existsPath($path)
	{
		if(is_dir($path))
		{
		    // given path is from computer root
				$document_root= preg_quote($_SERVER["DOCUMENT_ROOT"]);
				$document_root= preg_replace("/\/", "\\/", $document_root);
				if(preg_match("/^".$document_root."(.+)$/", $path, $preg))
				    $path= $preg[1];
		}
	  	preg_match("/^(.*)\/[^\/]+$/", $_SERVER["SCRIPT_NAME"], $preg);
		if(substr($path, 0, 1)!="/")
		    $path= $preg[1]."/".$path;
		if(substr($path, strlen($path)-1)!="/")
		    $path.= "/";
		$exist= is_dir($_SERVER["DOCUMENT_ROOT"].$path);
		return $exist;
	}
	function setFirstDisplayOrder($orderName)
	{
	    $this->sDisplayOrder= $orderName;
		$split= preg_split("/\//", $orderName);
		$count= 0;
		foreach($split as $path)
		{
			if($path)
				++$count;
		}
		$this->nFirstDeep= $count;
	}
	function create()
	{
	  	if(!$this->oContainer)
			    $this->oContainer= &$this;
		
		
	  	STObjectContainer::create();
		$this->setFirstTable($this->getTableName("gallery"));
		
		$this->oGalleryTable= &$this->needTable("gallery");
		//echo "exist gallery-tables";
		//st_print_r($this->tables);
		$this->oGalleryTable->limitByOwn(false);
		$this->insertByMainListLink("stget[gallery]=new");
		$this->deleteByContainerLink("stget[link][n]", "gallery");
	}	
	function noAdministration()
	{
	    $this->bAdmin= false;
	}
	function init()
	{//Tag::debug("stgallery");
	
		STObjectContainer::init();	
		$ownContainerName= $this->getName();
		$pkName= $this->oGalleryTable->getPkColumnName();
		$parentName= $this->getColumnFromTable("gallery", "parent");
		$subOrderName= $this->getColumnFromTable("gallery", "suborder");
		$typeName= $this->getColumnFromTable("gallery", "type");
		$uploadPathName= $this->getColumnFromTable("gallery", "uploadPath");
		$ftpPathName= $this->getColumnFromTable("gallery", "ftpPath");
		$pictureName= $this->getColumnFromTable("gallery", "picture");
		$deepName= $this->getColumnFromTable("gallery", "deep");
		$activeName= $this->getColumnFromTable("gallery", "active");
		$sortName= $this->getColumnFromTable("gallery", "sort");
		$orderName= $this->getColumnFromTable("gallery", "sortDirection");;
		$downloadName= $this->getColumnFromTable("gallery", "download");
		
		if(!$this->bAdmin)
		{
	    	$this->oGalleryTable->doInsert(false);
	    	$this->oGalleryTable->doUpdate(false);
	    	$this->oGalleryTable->doDelete(false);
			$this->oGalleryTable->setListCaption(false);
		}
		$action= $this->getAction();
		$params= new GetHtml();
		$params= $params->getArrayVars();
		$this->nAktOrderID= $params["stget"]["gallery"][$pkName];
		$nOrder= $params["stget"]["link"]["n"];
		if(Tag::isDebug())
		{
			$defDeep= $nOrder;
			if($defDeep===null)
				$defDeep= "undefined";
			Tag::echoDebug("stgallery", "Order deep from get-param is $defDeep");
		}
		
		if(!$this->nAktOrderID
			and
			$this->sDisplayOrder    )
		{
		    $g= new OSTDbSelector($this->oGalleryTable);
    		//$g->clearSelects();
    		$g->select("gallery", $pkName);
    		$where= new STDbWhere($subOrderName."='".$this->sDisplayOrder."'");
    		$where->andWhere($parentName." is null");				
    		$g->where($where);
    		$g->execute();
    		$this->nAktOrderID= $g->getSingleResult();
		}elseif(!$this->nAktOrderID)
			Tag::echoDebug("stgallery", "so check all root entrys in database with parent null");
		
		if($this->nAktOrderID)
		{//Tag::debug("db.statement");
        	$selector= new OSTDbSelector($this->oGalleryTable);
			$selector->where($pkName."=".$this->nAktOrderID);
			//$selector->limitByOwn(true);
			$selector->modifyForeignKey(false);
    		$selector->execute(MYSQL_ASSOC);
			$selectorResult= $selector->getRowResult();
			if(Tag::isDebug("stgallery"))
			{
				if($this->sDisplayOrder && !$nOrder)
					Tag::echoDebug("stgallery", "database result from first order '".$this->sDisplayOrder."':");
				else
					Tag::echoDebug("stgallery", "database result from aktual PK in get-param:");
				st_print_r($selectorResult,2);
			}
			if($nOrder<0 || !$nOrder)
				$nOrder= 0;
			
    		$nAktOrder= $selectorResult[$deepName];
			while(	$nAktOrder>$nOrder
					and
					$action==STLIST		)
			{
				$selector->where($pkName."=".$selectorResult[$parentName]);
				$selector->execute(MYSQL_ASSOC);
    			$selectorResult= $selector->getRowResult();
				if(Tag::isDebug("stgallery"))
    			{
					Tag::echoDebug("stgallery", "----------------------------------------------------------------------------");
    				Tag::echoDebug("stgallery", "calculated parent with ID ".$selectorResult[$pkName]);
    				st_print_r($selectorResult,2);
    			}
    			/*echo "<pre>";
    			st_print_r($selectorResult,2);
    			echo "</pre>";*/
				$nAktOrder= $selectorResult[$deepName];
				if(!$nAktOrder)
					$nAktOrder= 0;
			}
			if($nOrder===null)
				$nOrder= $nAktOrder;
		}
		if($nOrder<0 || !$nOrder)
			$nOrder= 0;
		if(	!$this->nAktOrderID
			and
			$action===STLIST		)
		{// layout is set for root-order type order
			//Tag::alert(!$this->uploadPath, "STDatabase::init()", "define before execute uploadPath() from STSubGalleryContainer");
			
			$this->nAktOrderID= 0;
				
			$this->oGalleryTable->select("suborder", "root-Ordner");
			//$this->oGalleryTable->select($this->oGalleryTable->getPkColumnName(), "open");
			$this->oGalleryTable->namedPkLink("suborder", $this->oOrderContainer);
			$this->oGalleryTable->where($parentName." is null");
			$this->oGalleryTable->indexCallback("changeWhere");
			$this->oGalleryTable->modifyForeignKey(false);
			$this->oGalleryTable->updateByLink("stget[link][n]=0", "root-Ordner");
			//$this->oGalleryTable->insertByLink("stget[".$this->oGalleryTable->getName()."][##pk]=");
			$this->deleteByContainerLink("stget[link][n]");
			$this->deleteByContainerLink("stget[".$this->oGalleryTable->getName()."][".$pkName."]");
			//echo "update suborder-link with link[n]=1<br />";
			//$this->oGalleryTable->updateByLink("stget[link][n]=1", "suborder");
			//$this->oGalleryTable->insertCallback("createDirectory");
		}elseif($action!=STLIST)
		{
			$this->oGalleryTable->modifyForeignKey(false);
			$this->oGalleryTable->where($pkName."=".$selectorResult[$pkName]);
			$this->oGalleryTable->useNoLimitationBefore();
			if(typeof($this, "STGalleryContainer"))
			{
				$this->oGalleryTable->select("type", "Typ");
				$this->oGalleryTable->select("suborder", "Ordner-Name");
				$this->oGalleryTable->select("sortDirection", "Absteigende Sortierung");
				$this->oGalleryTable->disabled("type", "image");
			}else
			{
				$this->oGalleryTable->useNoLimitationBefore();
				//$this->oGalleryTable->select("type", "Typ");
				//$this->oGalleryTable->disabled("type");
				$this->oGalleryTable->select("suborder", "Ordner-Name");
				$this->oGalleryTable->select("picture", "Bild");
				//$this->oGalleryTable->select("tumpnail", "Bild-Pfad");
				//$this->oGalleryTable->disabled("tumpnail");
				$this->oGalleryTable->select("userText", "Bild-Text");
				$this->oGalleryTable->select("before", "Text vor Link");
				$this->oGalleryTable->select("description", "Link-Anzeige");
				$this->oGalleryTable->select("behind", "Text nach Link");
				$this->oGalleryTable->select("sortDirection", "Absteigende Sortierung");
				$this->oGalleryTable->select("active", "Aktiv");
				if($selectorResult[$typeName]=="gallery")
					$this->oGalleryTable->select("download", "Download m�glichkeit");
				$size= $this->anPicSize["tumpnail"];
				$this->oGalleryTable->image("Bild", $selectorResult[$uploadPathName], 10, $size, $size);
			}
		}else
		{
			$parentID= $selectorResult[$pkName];
			$typeID= $selectorResult[$typeName];
			if(Tag::isDebug())
			{				
				if($this->getName()==$this->oPicContainer->getName())
					$typeID= "image";
				Tag::echoDebug("stgallery", "show all entrys with parentID $parentID");
				Tag::echoDebug("stgallery", "with the layout from type:$typeID");
			}
			/*if($nAktOrder>$nOrder)
			{
				$needParent= false;
				if($typeID=="image")
					$typeID= "gallery";
				elseif($typeID=="gallery")
					$typeID= "order";
				$parentID= $selectorResult[$parentName];
				if($parentID===null)
					$parentID= "null";
			}*/
		  	if($this->getName()==$this->oPicContainer->getName())
			{// layout is set for type image
			    $html= new GetHtml();
    			$ID= $html->getArrayVars();
    			$ID= $ID["stget"][$this->oGalleryTable->getName()][$pkName];					
    			
				if($selectorResult[$downloadName]=="Yes")
				{
					$this->oGalleryTable->select("picture");
					$this->oGalleryTable->download("picture");
				}
    			$this->oGalleryTable->select("showen");
				$this->oGalleryTable->select("description");
    			$this->oGalleryTable->image("showen");
    			$this->oGalleryTable->tdAttribute("showen", "align", "center", STLIST);
    			$this->oGalleryTable->modifyForeignKey(false);
    			$this->oGalleryTable->where($parentName."=".$parentID);
    			$this->oGalleryTable->setMaxRowSelect(1);
    			$this->oGalleryTable->LimitByOwn(false);//Tag::debug("db.statement");
    			$this->oGalleryTable->useNoLimitationBefore();
				$this->oGalleryTable->listLayout(STVERTICAL);
				$bASC= true;
				if($selectorResult[$orderName]=="DESC")
					$bASC= false;
				$this->oGalleryTable->orderBy($sortName, $bASC);	
	  			//$this->deleteByContainerLink("stget[link][n]");				
			
			}elseif($typeID=="gallery")
			{// layout is set for type gallery
				if( $selectorResult[$typeName]=="gallery"
					and
					$this->bAdmin				)
				{
        			$uploadPath= $selectorResult[$uploadPathName].$selectorResult[$subOrderName]."/";
        			$ftpPath= $selectorResult[$ftpPathName].$selectorResult[$subOrderName]."/";
					$this->checkFtpPhotos($uploadPath, $ftpPath, $selectorResult[$pkName], ($nAktOrder+1));
				}
					
    			$this->oGalleryTable->select("tumpnail", "Bild");
    			$this->oGalleryTable->tdAttribute("tumpnail", "align", "center", STLIST);
    			if(!$this->bAdmin)
    				$this->oGalleryTable->andWhere($activeName."='Yes'");
    			$this->oGalleryTable->andWhere($parentName."=".$parentID);
    			if($this->bAdmin)
    			{
    				$this->oGalleryTable->image("tumpnail");
    				$this->oGalleryTable->select("description", "Bild-Text");
    				$this->oGalleryTable->setMaxRowSelect(10);
    			}else
    			{
    				$this->oGalleryTable->imagePkLink("tumpnail", $this->oPicContainer);
    				$this->oGalleryTable->setMaxRowSelect(6);
    				$this->oGalleryTable->displayListInColumns(3);
    				$this->oGalleryTable->setListCaption($this->bAdmin);
    			}
    			$this->oGalleryTable->modifyForeignKey(false);
    			//$this->oGalleryTable->limitByOwn(false);
    			$this->oGalleryTable->useNoLimitationBefore();
				$bASC= true;
				if($selectorResult[$orderName]=="DESC")
					$bASC= false;
				$this->oGalleryTable->orderBy($sortName, $bASC);
				$this->oGalleryTable->insertByLink("stget[".$this->oGalleryTable->getName()."][".$deepName."]=".($selectorResult[$deepName]+1), "Bild");
				$this->oGalleryTable->insertByLink("stget[link][from][1][".$this->oGalleryTable->getName()."]=".$deepName, "Bild");
			}else
			{// layout is set for type order
				if($this->bAdmin)
				{
					$uploadPath= $selectorResult[$uploadPathName];
					$ftpPath= $selectorResult[$ftpPathName];
					$uploadPath.= $selectorResult[$subOrderName]."/";
					$ftpPath.= $selectorResult[$subOrderName]."/";
					$this->checkOrder($selectorResult[$pkName], $uploadPath, $ftpPath, ($nAktOrder+1));
				}
				
				$this->oGalleryTable->select("before", "Text vor Link");
				$this->oGalleryTable->select("description", "Link-Inhalt");
				$this->oGalleryTable->select("behind", "Text nach Link");
				if($this->bAdmin)
				{
					$this->oGalleryTable->select("type", "Typ");
					$this->oGalleryTable->select("active", "Aktiv");
				}
				$this->oGalleryTable->namedPkLink("description", $this->oOrderContainer);
				$this->oGalleryTable->tdAttribute("description", "align", "center", STLIST);
				$this->oGalleryTable->where($parentName."=".$parentID);
				$this->oGalleryTable->updateByLink("stget[link][n]=".($nOrder+1), "Link-Inhalt");
				$this->oGalleryTable->insertByLink("stget[".$this->oGalleryTable->getName()."][".$deepName."]=".($selectorResult[$deepName]+1), "Link-Inhalt");
				$this->oGalleryTable->insertByLink("stget[link][from][1][".$this->oGalleryTable->getName()."]=".$deepName, "Link-Inhalt");
				$this->oGalleryTable->setListCaption($this->bAdmin);
				$this->oGalleryTable->modifyForeignKey(false);
				$bASC= true;
				if($selectorResult[$orderName]=="DESC")
					$bASC= false;
				$this->oGalleryTable->orderBy($sortName, $bASC);
				
				if(!typeof($this, "STGalleryContainer"))
					$this->oGalleryTable->useNoLimitationBefore();
				if(!$this->bAdmin)
					$this->oGalleryTable->andWhere($activeName."='Yes'");
			}
			/*if(typeof($this, "STGalleryContainer"))
			{
				$this->deleteByContainerLink("stget[link][n]");
				//$this->deleteByContainerLink("stget[".$this->oGalleryTable->getName()."][".$pkName."]");
			}elseif($this->getName()!=$this->oPicContainer->getName())
			{*/
				$this->deleteByContainerLink("stget[link][n]");
				if($this->getName()!=$this->oPicContainer->getName())
					$nOrder-= 1;
				$this->updateByContainerLink("stget[link][n]=".$nOrder, $this->oOrderContainer->getName());
				//$this->deleteByContainerLink("stget[".$this->oGalleryTable->getName()."][".$pkName."]");
				//$this->insertByContainerLink("stget[".$this->oGalleryTable->getName()."][".$pkName."]=".$selectorResult[$parentName], $this->oOrderContainer->getName());
				//$this->updateByContainerLink("stget[link][r]=back");
			//}
			
		}	
		return true;
	}
	function install()
	{
	    STDbTableContainer::install();
		$this->createContainer();
	    $selector= new OSTDbSelector($this->oGalleryTable);
			$selector->count();
      	$selector->execute(MYSQL_ASSOC);
		$count= $selector->getSingleResult();
			
		if(!$count)
			$this->firstDbInserts($this->oGalleryTable);
	}
	function firstDbInserts($gallery)
	{//;st_print_r($this->asTableColumns,10);
		$uploadPath= $this->documentRoot.$this->uploadPath;
		
		$inserter= new STDbDefInserter($this, $gallery);
		$inserter->fillColumn($gallery->getPkColumnName(), 1);// so the PK by archiv always the same
		$inserter->fillColumn("suborder", 'archiv');
		$inserter->fillColumn("type", "order");
        $inserter->fillColumn("download", "No");
        $inserter->fillColumn("picture", "");
        $inserter->fillColumn("tumpnail", "");
        $inserter->fillColumn("userText", "");
        $inserter->fillColumn("type_text", "");
        $inserter->fillColumn("parent", "null");
		$inserter->fillColumn("deep", "0");
		$inserter->fillColumn("sort", "1");
		$inserter->fillColumn("sortDirection", "ASC");
		$inserter->fillColumn("active", "No");
        $inserter->fillColumn("uploadPath", $this->uploadPath);
        $inserter->fillColumn("ftpPath", $this->ftpPath);		
        $inserter->fillNextRow();
		
		$inserter->fillColumn($gallery->getPkColumnName(), 2);// so the PK by archiv always the same
        $inserter->fillColumn("suborder", 'gallery');
        $inserter->fillColumn("type", "order");
        $inserter->fillColumn("download", "No");
        $inserter->fillColumn("picture", "");
        $inserter->fillColumn("tumpnail", "");
        $inserter->fillColumn("userText", "");
        $inserter->fillColumn("type_text", "");
        $inserter->fillColumn("parent", "null");
		$inserter->fillColumn("deep", "0");
		$inserter->fillColumn("sort", "2");
		$inserter->fillColumn("sortDirection", "DESC");
		$inserter->fillColumn("active", "Yes");
        $inserter->fillColumn("uploadPath", $this->uploadPath);
        $inserter->fillColumn("ftpPath", $this->ftpPath);
		if($this->bPhpUpload && !is_dir($uploadPath."/gallery"))
			mkdir($uploadPath."/gallery");
        //$inserter->fillColumn($this->asTableColumns["next"]["column"], 1);
        if($inserter->execute())
        {
        	Tag::alert(1, "STSubGalleryContainer::execute", $inserter->getErrorString());
        }
	}
	function checkOrder($parent, $uploadPath, $ftpPath, $deep)
	{//STCheck::is_warning(1,"", "checkOrder($parent, $uploadPath, $ftpPath, $deep)<br />");
	
		if($parent===null)
			$parent= "null";
	    $gallery= new OSTDbSelector($this->oGalleryTable);
			$gallery->select("gallery", "max(sort)");
			$gallery->where($this->getColumnFromTable("gallery", "parent")."=".$parent);
			$gallery->execute();
			$lastSort= $gallery->getSingleResult();
			//echo "deep:$deep<br />";
			//echo "sort:$lastSort<br />";
			
			$gallery->clearSelects();
			$gallery->select("gallery", "suborder");
			$gallery->execute();
			$dbResult= $gallery->getSingleArrayResult();
			$dirResult= array();
			$noDir= array();
			$documentRoot= $this->documentRoot.$ftpPath;
				
			
			$dir= dir($documentRoot);
			while($directory= $dir->read())
			{
				if(is_dir($documentRoot.$directory))
				{
				    if( $directory!="."
					    and
						$directory!=".."    )
					{
				        if($noDir[$directory])
					        unset($noDir[$directory]);
					    else
				            $dirResult[$directory]= "";
					}
				}else
				{
				    preg_match("/^([^.]+)\.(jpg|jpeg|gif|png)$/i", $directory, $preg);
					if($preg[2])
					{
					    if(!isset($dirResult[$preg[1]]))
						    $noDir[$preg[1]]= true;
						$dirResult[$preg[1]]= $directory; 
					}    
				}
			}
				//echo "<pre>";
			if( is_array($dbResult)
			    and
				count($dbResult)  )
			{
			    $dbResult= array_flip($dbResult);
			}
			//st_print_r($dbResult);
			//st_print_r($dirResult);
			//st_print_r($noDir);
				
			$inserter= new STDbDefInserter($this, $this->oGalleryTable);
			foreach($dirResult as $dir=>$file)
			{//echo $dir."<br />";
			    if( !isset($dbResult[$dir])
				    and
					!isset($noDir[$dir])    )
				{
					$subDir= dir($documentRoot.$dir);
					$type= "";
					while($f= $subDir->read())
					{
					    if( $f!="."
						    and
							$f!=".." )
						{//echo "check $
						    if(is_dir($documentRoot.$dir."/".$f))
							{
							    $type= "order";
								break;
							}elseif(preg_match("/^([^.]+)\.(jpg|jpeg|gif|png)$/i", $f))
								$type= "gallery";
						}
					}
					if($type)
					{
    				    $inserter->fillColumn("suborder", $dir);
    					$inserter->fillColumn("parent", $parent);
    					$inserter->fillColumn("deep", $deep);
    					++$lastSort;
    					$inserter->fillColumn("sort", $lastSort);
    					$inserter->fillColumn("type", $type);
						if($type=="order")
							$inserter->fillColumn("sortDirection", "DESC");
						else
							$inserter->fillColumn("sortDirection", "ASC");
    					$inserter->fillColumn("active", "No");
    					//$description= '<a href="\$params">';
    					if($file)   
						{
							$inserter->fillColumn("picture", $ftpPath.$file);
							$inserter->fillColumn("tumpnail", $ftpPath.$file);
    						$description= '<img src="'.$ftpPath.$file.'" alt="'.$dir.'" border="0">';
    					}else
    						$description= $dir;
							
    					$inserter->fillColumn("description", $description);
						$inserter->fillColumn("download", "No");
    					$inserter->fillColumn("uploadPath", $uploadPath);
    					$inserter->fillColumn("ftpPath", $ftpPath);
    					$inserter->fillColumn("from_date", "sysdate()");					
                		if(	$this->bPhpUpload 
							and
		  					$uploadPath!=$ftpPath
							and
							!is_dir($this->documentRoot.$uploadPath.$dir)	)
						{
                			mkdir($this->documentRoot.$uploadPath.$dir);
						}
						if(	$this->bPhpUpload 
							and
							!is_dir($this->documentRoot.$uploadPath.$dir."/tumpnails")	)
						{
							mkdir($this->documentRoot.$uploadPath.$dir."/tumpnails");
						}
    					$inserter->fillNextRow();
					}
				}
			}
			$inserter->execute();
	}	
	function checkFtpPhotos($uploadPath, $ftpPath, $parent, $deep)
	{//echo "checkFtpPhotos('$uploadPath', '$ftpPath', $parent, $deep)<br />";
		global	$db;
		
		$files= array();
		$fileSize= 0;
		$directory= dir($this->documentRoot.$ftpPath);
		while($file= $directory->read())
		{
			if( is_file($this->documentRoot.$ftpPath.$file)
				and
				preg_match("/^(schowen_|tump_)?.+\.(jpg|jpeg|png|gif)$/i", $file, $preg)  )
			{
			    if(!$preg[1])
				{
			 	    $files[$ftpPath.$file]= date("Y:m:d", filemtime($this->documentRoot.$ftpPath.$file));
					$fileSize+= filesize($this->documentRoot.$ftpPath.$file);
				}
			}
		}//st_print_r($files);
		//echo "filesize is ".$fileSize."<br />";
		$gallery= new OSTDbSelector($this->oGalleryTable);
		$gallery->select("gallery", "ID");
		$gallery->select("gallery", "from_date");
		$gallery->select("gallery", "picture");
		$gallery->select("gallery", "tumpnail");
		$gallery->select("gallery", "showen");
		$gallery->where($this->getColumnFromTable("gallery", "parent")."=".$parent);
		$gallery->modifyForeignKey(false);
		//Tag::debug("db.statement");
		$gallery->execute();
		$dbEntrys= $gallery->getResult();
		$lostPhotos= array();// images in DB but not in directory
		
		foreach($dbEntrys as $key=>$file)
		{
			//preg_match("/.*\/([^\/]+)$/", $file[2], $preg);
			//st_print_r($preg);
			//$file= $preg[1];
			if(isset($files[$file[2]]))
				unset($files[$file[2]]);
			else
				$lostPhotos[]= $key;
		}
		if(Tag::isDebug("stgallery"))
		{
			if(count($dbEntrys))
			{
				echo "<b>files in database:</b> (parent:$parent)";
				st_print_r($dbEntrys,2);
			}else
				echo "<b>no files in database:</b> (parent:$parent)<br />";
			if(count($files))
			{
				echo "<br /><b>files on harddisk:</b> (".$this->documentRoot.$ftpPath.") <b>but not in database";
				st_print_r($files);
			}else
				echo "<br /><b>no files be on harddisk:</b> (".$this->documentRoot.$ftpPath.") <b>which are not in database</b><br />";
			if(count($lostPhotos))
			{
				echo "<b>files in db which are not on harddisk:</b><br />";
				st_print_r($lostPhotos);
			}else
				echo "<b>no file in db but not on harddisk</b><br /><br />";
				
		}
		if(	count($files)
			or
			count($lostPhotos))
		{
			echo "<h1>create new Index ...</h1><br />";
			$gallery->clearSelects();
			$gallery->count("*");
			$gallery->execute();
			$lastSort= $gallery->getSingleResult();
			/*echo "new Pictures:<br />";
			st_print_r($files);
			echo "Pictures to delete:<br />";
			st_print_r($lostPhotos);
				echo "</pre>";*/
			//exit;
			foreach($files as $imageName=>$date)
			{
				$tumpnailImage= null;
				$showTumpnailImage= null;
				if($this->tumpnail($uploadPath."tumpnails/", $ftpPath, $imageName, $tumpnailImage, $showTumpnailImage))
				{
				  $inserter= new STDbDefInserter($this, $this->oGalleryTable);
					$inserter->fillColumn("type", "image");
					$inserter->fillColumn("picture", $imageName);
					$inserter->fillColumn("tumpnail", $tumpnailImage);
					$inserter->fillColumn("showen", $showTumpnailImage);
					$inserter->fillColumn("parent", $parent);
					++$lastSort;
					$inserter->fillColumn("sort", $lastSort);
					$inserter->fillColumn("sortDirection", "ASC");
					$inserter->fillColumn("deep", $deep);
					$inserter->fillColumn("active", "Yes");
					$inserter->fillColumn("from_date", $date);
					$inserter->fillColumn("projectID", 0);
					
					if($inserter->execute())
					{
						unlink($this->documentRoot.$tumpnailImage);
						unlink($this->documentRoot.$showTumpnailImage);
						echo "<b>ERROR:</b> cannot insert image $imageName in database<br />";
					}
				}else
					echo "<b>ERROR:</b> cannot create tumpnails for image $imageName<br />";
					//exit;
			}
			foreach($lostPhotos as $key)
			{
				unlink($this->documentRoot.$dbEntrys[$key][3]);
				unlink($this->documentRoot.$dbEntrys[$key][4]);
				$statement= "delete from ".$gallery->getName()." where ID=".$dbEntrys[$key][0];
				$db->fetch($statement);
			}				
		}
		if($fileSize>31494144)//=30 MB
			return false;
		return true;
	}
		function tumpnail($uploadPath, $ftpPath, $imageName, &$tumpnailImage, &$showTumpnailImage)
		{//echo "documentRoot:".$this->documentRoot."<br />";
			$imageSize= getimagesize($this->documentRoot.$imageName);
			$mainLength= $this->anPicSize["tumpnail"];
			$showLength= $this->anPicSize["showen"];
			if($imageSize[0]>$imageSize[1])
			{
				$width= $mainLength;
				$showWidth= $showLength;
				$height= $imageSize[1]/($imageSize[0]/$mainLength);
				$showHeight= $imageSize[1]/($imageSize[0]/$showLength);
			}else
			{
				$height= $mainLength;
				$showHeight= $showLength;
				$width= $imageSize[0]/($imageSize[1]/$mainLength);
				$showWidth= $imageSize[0]/($imageSize[1]/$showLength);
			}
			if(preg_match("/.(jpg|jpeg)$/i", $imageName))
				$image = imagecreatefromjpeg($this->documentRoot.$imageName);
			else
				$image = imagecreatefromgif($this->documentRoot.$imageName);
			$newImage= imagecreatetruecolor($width, $height);
			$showImage= imagecreatetruecolor($showWidth, $showHeight);
			$bError= imagecopyresized($showImage, $image, 0, 0, 
								0, 0, $showWidth, $showHeight, $imageSize[0], $imageSize[1]);
			if($bError)
			{
				$bError= imagecopyresized($newImage, $image, 0, 0, 
								0, 0, $width, $height, $imageSize[0], $imageSize[1]);
			}
			preg_match("/([^\/]+)\.(jpg|jpeg|gif|png)$/i", $imageName, $preg);
			//st_print_r($preg);
			$count= 1;
			do{
				$tumpnailImage= $uploadPath."tump_".$preg[1]."[".$count."].jpg";
				++$count;
				//echo $tumpnailImage."<br />";
			}while(file_exists($this->documentRoot.$tumpnailImage));
			$count= 1;
			do{
				$showTumpnailImage= $uploadPath."schowen_".$preg[1]."[".$count."].jpg";
				++$count;
				//echo $showTumpnailImage."<br />";
			}while(file_exists($this->documentRoot.$showTumpnailImage));
			if($bError)
			{									
				$bError= imagejpeg($newImage, $this->documentRoot.$tumpnailImage);
				if($bError)
					$bError= imagejpeg($showImage, $this->documentRoot.$showTumpnailImage);
				else
					unlink($this->documentRoot.$tumpnailImage);
			}//exit;
			return $bError;
		}
	function exectue($stsidecreator)
	{
		$this->createLayout();
		STObjectContainer::execute($stsidecreator);
	}
}
		function createDirectory($path, $onDir)
		{
    		$dir_exists= false;
    		$directory= dir($onDir);
    		while($entry= $directory->read())
    		{
    			if(	$entry==$path
    				and
    				!is_file($onDir."/".$path))
    			{
    				$dir_exists= true;
    				break;
    			}
    		}
    		if(!$dir_exists)
    			mkdir($onDir."/".$path);
		}

function getFromWhereID($where)
{
	foreach($where->array as $content)
	{
		if(typeof($content, "STDbWhere"))
		{
			$ID= getFromWhereID($content);
			if($ID!==null)
				return $ID;
		}else
		{
			if(preg_match("/ID=([0-9]+)/", $content, $preg))
			{
				return $preg[1];
			}
		}
	}
	return null;
}
function changeWhere(&$Callback)
{
	$where= $Callback->getWhere();
	$ID= getFromWhereID($where);
	if($ID!==null)
		$Callback->setWhere("parent=".$ID);
}

?>
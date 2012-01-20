<?php

	global $CONFIG;

	gatekeeper();

	$old_context = get_context();

	$page_owner_guid 	= get_input("page_owner");
	$page_owner 		= get_entity($page_owner_guid);
	$folder_guid 		= get_input("folder_guid", 0);
	$draw_page 			= get_input("draw_page", true);

	$sort_by 			= get_input('sort_by', 'e.time_created');
	$direction 			= get_input('direction', 'ASC');
	$time_option 		= get_input("time_option", 'date');

	$vars['time_option'] = $time_option;
	if(!empty($page_owner) && (($page_owner instanceof ElggUser) || ($page_owner instanceof ElggGroup)))
	{
		// set page owner & context
		set_page_owner($page_owner_guid);
		set_context("file");

		group_gatekeeper();

		$wheres = array();
		$wheres[] = "NOT EXISTS (
					SELECT 1 FROM {$CONFIG->dbprefix}entity_relationships r 
					WHERE r.guid_two = e.guid AND
					r.relationship = '" . FILE_TOOLS_RELATIONSHIP . "')";

		$files_options = array(
				"type" => "object",
				"subtype" => "file",
				"limit" => false,
				"container_guid" => $page_owner_guid
			);

		$files_options["joins"][] = "JOIN {$CONFIG->dbprefix}objects_entity oe on oe.guid = e.guid";

		if($sort_by == 'simpletype')
		{
			$files_options["order_by_metadata"] = array('name' => 'mimetype', 'direction' => $direction);
		}
		else
		{
			$files_options["order_by"] = $sort_by . ' ' . $direction;
		}

		if($folder_guid !== false)
		{
			if($folder_guid == 0)
			{
				if(get_plugin_setting("user_folder_structure", "file_tools") == "yes")
				{
					$files_options["wheres"] = $wheres;
				}
				
				$files = elgg_get_entities($files_options);	
			} 
			else
			{
				$folder = get_entity($folder_guid);

				$files_options["relationship"] = FILE_TOOLS_RELATIONSHIP;
				$files_options["relationship_guid"] = $folder_guid;
				$files_options["inverse_relationship"] = false;

				$files = elgg_get_entities_from_relationship($files_options);	
			}	
		}

		if(get_plugin_setting("user_folder_structure", "file_tools") == "yes")
		{
			if(!$draw_page)
			{
				echo elgg_view("file_tools/list/files", array("folder" => $folder, "files" => $files, 'sort_by' => $sort_by, 'direction' => $direction, 'time_option' => $time_option));
			}
			else
			{			
				// get data for tree
				$folders = file_tools_get_folders($page_owner_guid, $vars['vars']);
	
				// default lists all unsorted files
				if($folder_guid === false)
				{
					if(get_plugin_setting("user_folder_structure", "file_tools") == "yes")
					{
						$files_options["wheres"] = $wheres;
					}
					
					$files = elgg_get_entities($files_options);
				}
				
				
				// build page elements
				$tree = '';
				if(get_plugin_setting("user_folder_structure", "file_tools") == "yes")
				{
					$tree = elgg_view("file_tools/list/tree", array("folder" => $folder, "folders" => $folders));
				}
	
				$body = '<div id="file_tools_list_files_container">' . elgg_view("ajax/loader") . '</div>
						<div class="contentWrapper">';
	
				if(page_owner_entity()->canEdit())
				{
					$body .= '<a id="file_tools_action_bulk_delete" href="javascript:void(0);">Delete selected</a> | ';
				} 
	
				$body .= '<a id="file_tools_action_bulk_download" href="javascript:void(0);">Download selected</a>
							<a id="file_tools_select_all" style="float: right;" href="javascript:void(0);">Select all</a>
						</div>';
			
	
				$title = elgg_view_title($title_text);

				page_draw($title_text, elgg_view_layout("two_column_left_sidebar", "", $title . $body, $tree));
			}
		}
		else
		{
			$title_text = elgg_echo("file:yours");

			$offset = (int)get_input('offset', 0);
			$title = elgg_view_title($title_text);

			$body = elgg_list_entities(array('types' => 'object', 'subtypes' => 'file', 'container_guid' => page_owner(), 'limit' => 10, 'offset' => $offset, 'full_view' => false));

			page_draw($title_text, elgg_view_layout("two_column_left_sidebar", "", $title . $body, $tree));
		}
	}
	else
	{
		forward();
	}
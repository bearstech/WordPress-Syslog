<?php

/**
 * Logger for events stored earlier than v2
 * and for events added via simple_history_add
 *
 * @since 2.0
 */
class SimpleLegacyLogger extends SimpleLogger
{

	/**
	 * Unique slug for this logger
	 * Will be saved in DB and used to associate each log row with its logger
	 */
	public $slug = "SimpleLegacyLogger";

	public function __construct() {
		
		// $this->info(__CLASS__ . " construct()");

	}

	/**
	 * Get array with information about this logger
	 * 
	 * @return array
	 */
	function getInfo() {

		$arr_info = array(			
			"name" => "Legacy Logger",
			"description" => "Formats old events",
			"capability" => "edit_pages",
			"messages" => array(
			),
			/*
			
			 "labels" => array(
				"search" => array(
					"label" => _x("Export", "Export logger: search", "simple-history"),
					"options" => array(
						_x("Exports created", "Core updates logger: search", "simple-history") => array(
							"created_export"
						),						
					)
				) // end search array
			) // end labels
			*/

		);
		
		return $arr_info;

	}


}


<?php
/**
 * The main Plugin API class.
 *
 * Allows for creating actions and filters and hooking functions, and methods. 
 * The functions or methods will be run when the action or filter is called.
 *
 * Based on the amazing plugin API that is built into WordPress.
 *
 * @author tyxla
 * @version 1.0
 */
class Plugin_API {

	/**
	 * Contains all filters and their hooked functions and methods.
	 *
	 * @static
	 *
	 * @var array
	 */
	static $filters = array();
	
	/**
	 * Contains all actions and their hooked functions and methods.
	 *
	 * @static
	 *
	 * @var array
	 */
	static $actions = array();

	/**
	 * Tracks the actions and filters that need to be merged for later.
	 *
	 * @static
	 *
	 * @var array
	 */
	static $merged_filters = array();

	/**
	 * Stores the list of current filters with the current one last.
	 *
	 * @static
	 *
	 * @var array
	 */
	static $current_filter = array();

	/**
	 * Hook a function or method to a specific filter.
	 *
	 * @static
	 * @access public
	 *
	 * @param string   $tag             The name of the filter to hook the $function_to_add callback to.
	 * @param callback $function_to_add The callback to be run when the filter is applied.
	 * @param int      $priority        Optional. Used to specify the order in which the functions
	 *                                  associated with a particular action are executed. Default 10.
	 *                                  Lower numbers correspond with earlier execution,
	 *                                  and functions with the same priority are executed
	 *                                  in the order in which they were added to the action.
	 * @param int      $accepted_args   Optional. The number of arguments the function accepts. Default 1.
	 * @return boolean true
	 */
	static function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		$idx = self::filter_build_unique_id($tag, $function_to_add, $priority);

		self::$filters[$tag][$priority][$idx] = array(
			'function' => $function_to_add, 
			'accepted_args' => $accepted_args
		);

		if ( isset( self::$merged_filters[ $tag ] ) ) {
			unset( self::$merged_filters[ $tag ] );
		}

		return true;
	}

	/**
	 * Check if any filter has been registered for a hook.
	 *
	 * @static
	 * @access public
	 *
	 * @param string        $tag               The name of the filter hook.
	 * @param callback|bool $function_to_check Optional. The callback to check for. Default false.
	 * @return bool|int If $function_to_check is omitted, returns boolean for whether the hook has
	 *                  anything registered. When checking a specific function, the priority of that
	 *                  hook is returned, or false if the function is not attached. When using the
	 *                  $function_to_check argument, this function may return a non-boolean value
	 *                  that evaluates to false (e.g.) 0, so use the === operator for testing the
	 *                  return value.
	 */
	static function has_filter($tag, $function_to_check = false) {

		$has = ! empty( self::$filters[ $tag ] );

		// Make sure at least one priority has a filter callback
		if ( $has ) {
			$exists = false;
			foreach ( self::$filters[ $tag ] as $callbacks ) {
				if ( ! empty( $callbacks ) ) {
					$exists = true;
					break;
				}
			}

			if ( ! $exists ) {
				$has = false;
			}
		}

		if ( false === $function_to_check || false == $has ) {
			return $has;
		}

		if ( !$idx = self::filter_build_unique_id($tag, $function_to_check, false) ) {
			return false;
		}

		foreach ( (array) array_keys(self::$filters[$tag]) as $priority ) {
			if ( isset(self::$filters[$tag][$priority][$idx]) ) {
				return $priority;
			}
		}

		return false;
	}

	/**
	 * Call the functions added to a filter hook.
	 *
	 * @static
	 * @access public
	 *
	 * @param string $tag   The name of the filter hook.
	 * @param mixed  $value The value on which the filters hooked to $tag are applied on.
	 * @param mixed  $var   Additional variables passed to the functions hooked to $tag.
	 * @return mixed The filtered value after all hooked functions are applied to it.
	 */
	static function apply_filters( $tag, $value ) {
		$args = array();

		// Do 'all' actions first.
		if ( isset(self::$filters['all']) ) {
			self::$current_filter[] = $tag;
			$args = func_get_args();
			self::call_all_hook($args);
		}

		if ( !isset(self::$filters[$tag]) ) {
			if ( isset(self::$filters['all']) ) {
				array_pop(self::$current_filter);
			}
			return $value;
		}

		if ( !isset(self::$filters['all']) ) {
			self::$current_filter[] = $tag;
		}

		// Sort.
		if ( !isset( self::$merged_filters[ $tag ] ) ) {
			ksort(self::$filters[$tag]);
			self::$merged_filters[ $tag ] = true;
		}

		reset( self::$filters[ $tag ] );

		if ( empty($args) ) {
			$args = func_get_args();
		}

		do {
			foreach( (array) current(self::$filters[$tag]) as $the_ ) {
				if ( !is_null($the_['function']) ){
					$args[1] = $value;
					$params = array_slice($args, 1, (int) $the_['accepted_args']);
					$value = call_user_func_array($the_['function'], $params);
				}
			}

		} while ( next(self::$filters[$tag]) !== false );

		array_pop( self::$current_filter );

		return $value;
	}

	/**
	 * Execute functions hooked on a specific filter hook, specifying arguments in an array.
	 *
	 * @static
	 * @access public
	 *
	 * @param string $tag  The name of the filter hook.
	 * @param array  $args The arguments supplied to the functions hooked to $tag.
	 * @return mixed The filtered value after all hooked functions are applied to it.
	 */
	static function apply_filters_ref_array($tag, $args) {
		// Do 'all' actions first
		if ( isset(self::$filters['all']) ) {
			self::$current_filter[] = $tag;
			$all_args = func_get_args();
			self::call_all_hook($all_args);
		}

		if ( !isset(self::$filters[$tag]) ) {
			if ( isset(self::$filters['all']) ) {
				array_pop(self::$current_filter);
			}
			return $args[0];
		}

		if ( !isset(self::$filters['all']) ) {
			self::$current_filter[] = $tag;
		}

		// Sort
		if ( !isset( self::$merged_filters[ $tag ] ) ) {
			ksort(self::$filters[$tag]);
			self::$merged_filters[ $tag ] = true;
		}

		reset( self::$filters[ $tag ] );

		do {
			foreach( (array) current(self::$filters[$tag]) as $the_ ) {
				if ( !is_null($the_['function']) ) {
					$params = array_slice($args, 0, (int) $the_['accepted_args']);
					$args[0] = call_user_func_array($the_['function'], $params);
				}
			}

		} while ( next(self::$filters[$tag]) !== false );

		array_pop( self::$current_filter );

		return $args[0];
	}

	/**
	 * Removes a function from a specified filter hook.
	 *
	 * @static
	 * @access public
	 *
	 * @param string   $tag                The filter hook to which the function to be removed is hooked.
	 * @param callback $function_to_remove The name of the function which should be removed.
	 * @param int      $priority           Optional. The priority of the function. Default 10.
	 * @return boolean Whether the function existed before it was removed.
	 */
	static function remove_filter( $tag, $function_to_remove, $priority = 10 ) {
		$function_to_remove = self::filter_build_unique_id( $tag, $function_to_remove, $priority );

		$r = isset( self::$filters[ $tag ][ $priority ][ $function_to_remove ] );

		if ( true === $r ) {
			unset( self::$filters[ $tag ][ $priority ][ $function_to_remove ] );

			if ( empty( self::$filters[ $tag ][ $priority ] ) ) {
				unset( self::$filters[ $tag ][ $priority ] );
			}

			if ( empty( self::$filters[ $tag ] ) ) {
				self::$filters[ $tag ] = array();
			}

			if ( isset( self::$merged_filters[ $tag ] ) ) {
				unset( self::$merged_filters[ $tag ] );	
			}
		}

		return $r;
	}

	/**
	 * Remove all of the hooks from a filter.
	 *
	 * @static
	 * @access public
	 *
	 * @param string   $tag      The filter to remove hooks from.
	 * @param int|bool $priority Optional. The priority number to remove. Default false.
	 * @return bool True when finished.
	 */
	static function remove_all_filters( $tag, $priority = false ) {
		if ( isset( self::$filters[ $tag ]) ) {
			if ( false !== $priority && isset( self::$filters[ $tag ][ $priority ] ) ) {
				self::$filters[ $tag ][ $priority ] = array();
			} else {
				self::$filters[ $tag ] = array();
			}
		}

		unset( self::$merged_filters[ $tag ] );

		return true;
	}

	/**
	 * Retrieve the name of the current filter or action.
	 *
	 * @static
	 * @access public
	 *
	 * @return string Hook name of the current filter or action.
	 */
	static function current_filter() {
		return end( self::$current_filter );
	}

	/**
	 * Retrieve the name of a filter currently being processed.
	 *
	 * @static
	 * @access public
	 *
	 * @param null|string $filter Optional. Filter to check. Defaults to null, which
	 *                            checks if any filter is currently being run.
	 * @return bool Whether the filter is currently in the stack.
	 */
	static function doing_filter( $filter = null ) {
		if ( null === $filter ) {
			return ! empty( self::$current_filter );
		}

		return in_array( $filter, self::$current_filter );
	}

	/**
	 * Retrieve the name of an action currently being processed.
	 *
	 * @static
	 * @access public
	 *
	 * @param string|null $action Optional. Action to check. Defaults to null, which checks
	 *                            if any action is currently being run.
	 * @return bool Whether the action is currently in the stack.
	 */
	static function doing_action( $action = null ) {
		return self::doing_filter( $action );
	}

	/**
	 * Hooks a function on to a specific action.
	 *
	 * @static
	 * @access public
	 *
	 * @param string   $tag             The name of the action to which the $function_to_add is hooked.
	 * @param callback $function_to_add The name of the function you wish to be called.
	 * @param int      $priority        Optional. Used to specify the order in which the functions
	 *                                  associated with a particular action are executed. Default 10.
	 *                                  Lower numbers correspond with earlier execution,
	 *                                  and functions with the same priority are executed
	 *                                  in the order in which they were added to the action.
	 * @param int      $accepted_args   Optional. The number of arguments the function accept. Default 1.
	 * @return bool Will always return true.
	 */
	static function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
		return self::add_filter($tag, $function_to_add, $priority, $accepted_args);
	}

	/**
	 * Execute functions hooked on a specific action hook.
	 *
	 * @static
	 * @access public
	 *
	 * @param string $tag The name of the action to be executed.
	 * @param mixed  $arg Optional. Additional arguments which are passed on to the
	 *                    functions hooked to the action. Default empty.
	 * @return null Will return null if $tag does not exist in the Plugin_API::$filters array.
	 */
	static function do_action($tag, $arg = '') {
		if ( ! isset(self::$actions[$tag]) ) {
			self::$actions[$tag] = 1;
		} else {
			++self::$actions[$tag];
		}

		// Do 'all' actions first
		if ( isset(self::$filters['all']) ) {
			self::$current_filter[] = $tag;
			$all_args = func_get_args();
			self::call_all_hook($all_args);
		}

		if ( !isset(self::$filters[$tag]) ) {
			if ( isset(self::$filters['all']) ) {
				array_pop(self::$current_filter);
			}
			return;
		}

		if ( !isset(self::$filters['all']) ) {
			self::$current_filter[] = $tag;
		}

		$args = array();
		if ( is_array($arg) && 1 == count($arg) && isset($arg[0]) && is_object($arg[0]) ) { // array(&$this)
			$args[] =& $arg[0];
		} else {
			$args[] = $arg;
		}

		for ( $a = 2; $a < func_num_args(); $a++ ) {
			$args[] = func_get_arg($a);
		}

		// Sort
		if ( !isset( self::$merged_filters[ $tag ] ) ) {
			ksort(self::$filters[$tag]);
			self::$merged_filters[ $tag ] = true;
		}

		reset( self::$filters[ $tag ] );

		do {
			foreach ( (array) current(self::$filters[$tag]) as $the_ ) {
				if ( !is_null($the_['function']) ) {
					$params = array_slice($args, 0, (int) $the_['accepted_args']);
					call_user_func_array($the_['function'], $params);
				}
			}

		} while ( next(self::$filters[$tag]) !== false );

		array_pop(self::$current_filter);
	}

	/**
	 * Retrieve the number of times an action is fired.
	 *
	 * @static
	 * @access public
	 *
	 * @param string $tag The name of the action hook.
	 * @return int The number of times action hook $tag is fired.
	 */
	static function did_action($tag) {
		if ( ! isset( self::$actions[ $tag ] ) ) {
			return 0;
		}

		return self::$actions[$tag];
	}

	/**
	 * Execute functions hooked on a specific action hook, specifying arguments in an array.
	 *
	 * @static
	 * @access public
	 *
	 * @param string $tag  The name of the action to be executed.
	 * @param array  $args The arguments supplied to the functions hooked to <tt>$tag</tt>
	 * @return null Will return null if $tag does not exist in self::$filters array
	 */
	static function do_action_ref_array($tag, $args) {
		if ( ! isset(self::$actions[$tag]) ) {
			self::$actions[$tag] = 1;
		} else {
			++self::$actions[$tag];
		}

		// Do 'all' actions first
		if ( isset(self::$filters['all']) ) {
			self::$current_filter[] = $tag;
			$all_args = func_get_args();
			self::call_all_hook($all_args);
		}

		if ( !isset(self::$filters[$tag]) ) {
			if ( isset(self::$filters['all']) ) {
				array_pop(self::$current_filter);
			}
			return;
		}

		if ( !isset(self::$filters['all']) ) {
			self::$current_filter[] = $tag;
		}

		// Sort
		if ( !isset( self::$merged_filters[ $tag ] ) ) {
			ksort(self::$filters[$tag]);
			self::$merged_filters[ $tag ] = true;
		}

		reset( self::$filters[ $tag ] );

		do {
			foreach( (array) current(self::$filters[$tag]) as $the_ ) {
				if ( !is_null($the_['function']) ) {
					$params = array_slice($args, 0, (int) $the_['accepted_args']);
					call_user_func_array($the_['function'], $params);
				}
			}

		} while ( next(self::$filters[$tag]) !== false );

		array_pop(self::$current_filter);
	}

	/**
	 * Check if any action has been registered for a hook.
	 *
	 * @static
	 * @access public
	 *
	 * @param string        $tag               The name of the action hook.
	 * @param callback|bool $function_to_check Optional. The callback to check for. Default false.
	 * @return bool|int If $function_to_check is omitted, returns boolean for whether the hook has
	 *                  anything registered. When checking a specific function, the priority of that
	 *                  hook is returned, or false if the function is not attached. When using the
	 *                  $function_to_check argument, this function may return a non-boolean value
	 *                  that evaluates to false (e.g.) 0, so use the === operator for testing the
	 *                  return value.
	 */
	static function has_action($tag, $function_to_check = false) {
		return self::has_filter($tag, $function_to_check);
	}

	/**
	 * Removes a function from a specified action hook.
	 *
	 * @static
	 * @access public
	 *
	 * @param string   $tag                The action hook to which the function to be removed is hooked.
	 * @param callback $function_to_remove The name of the function which should be removed.
	 * @param int      $priority           Optional. The priority of the function. Default 10.
	 * @return boolean Whether the function is removed.
	 */
	static function remove_action( $tag, $function_to_remove, $priority = 10 ) {
		return self::remove_filter( $tag, $function_to_remove, $priority );
	}

	/**
	 * Remove all of the hooks from an action.
	 *
	 * @static
	 * @access public
	 *
	 * @param string   $tag      The action to remove hooks from.
	 * @param int|bool $priority The priority number to remove them from. Default false.
	 * @return bool True when finished.
	 */
	static function remove_all_actions($tag, $priority = false) {
		return self::remove_all_filters($tag, $priority);
	}

	/**
	 * Call the 'all' hook, which will process the functions hooked into it.
	 *
	 * @static
	 * @access private
	 *
	 * @param array $args The collected parameters from the hook that was called.
	 */
	private static function call_all_hook($args) {
		reset( self::$filters['all'] );
		do {
			foreach( (array) current(self::$filters['all']) as $the_ ) {
				if ( !is_null($the_['function']) ) {
					call_user_func_array($the_['function'], $args);
				}
			}

		} while ( next(self::$filters['all']) !== false );
	}

	/**
	 * Build Unique ID for storage and retrieval.
	 *
	 * @static
	 * @access private
	 *
	 * @param string   $tag      Used in counting how many hooks were applied
	 * @param callback $function Used for creating unique id
	 * @param int|bool $priority Used in counting how many hooks were applied. If === false
	 *                           and $function is an object reference, we return the unique
	 *                           id only if it already has one, false otherwise.
	 * @return string|bool Unique ID for usage as array key or false if $priority === false
	 *                     and $function is an object reference, and it does not already have
	 *                     a unique id.
	 */
	private static function filter_build_unique_id( $tag, $function, $priority ) {
		static $filter_id_count = 0;

		if ( is_string($function) ) {
			return $function;
		}

		if ( is_object($function) ) {
			// Closures are currently implemented as objects
			$function = array( $function, '' );
		} else {
			$function = (array) $function;
		}

		if (is_object($function[0]) ) {
			// Object Class Calling
			if ( function_exists('spl_object_hash') ) {
				return spl_object_hash($function[0]) . $function[1];
			} else {
				$obj_idx = get_class($function[0]) . $function[1];
				if ( !isset($function[0]->filter_id) ) {
					if ( false === $priority ) {
						return false;
					}
					
					$obj_idx .= isset(self::$filters[$tag][$priority]) ? count((array)self::$filters[$tag][$priority]) : $filter_id_count;
					$function[0]->filter_id = $filter_id_count;
					++$filter_id_count;
				} else {
					$obj_idx .= $function[0]->filter_id;
				}

				return $obj_idx;
			}
		} else if ( is_string($function[0]) ) {
			// Static Calling
			return $function[0] . '::' . $function[1];
		}
	}

}
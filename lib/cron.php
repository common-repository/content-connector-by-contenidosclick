<?php
/**
 *
 * Add cron task callback
 *
 */
if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'CONTENT_CONNECTOR_CRON' ) ) {

	final class CONTENT_CONNECTOR_CRON {


		public function schedule_task($task) {

			if( ! $task ) {
				return false;
			}

			if( wp_next_scheduled( $task['hook'] ) ){
				wp_clear_scheduled_hook($task['hook']);
			}

			wp_schedule_event($task['timestamp'], $task['recurrence'], $task['hook']);
			return true;
		}


    public function unschedule_task($hook) {

      wp_clear_scheduled_hook($hook);
    }
	}
}

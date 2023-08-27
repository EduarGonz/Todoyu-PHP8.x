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
 * Event Reminder Email
 *
 * @package		Todoyu
 * @subpackage	Calendar
 */
class TodoyuCalendarReminderEmail extends TodoyuCalendarReminder {

	/**
	 * Get scheduled email reminder time
	 *
	 * @return	integer
	 */
	public function getDateRemindEmail() {
		return $this->getInt('date_remindemail');
	}



	/**
	 * Get amount of time before event when to send reminder email
	 *
	 * @return	boolean|Integer
	 */
	public function getAdvanceTime($type = CALENDAR_TYPE_EVENTREMINDER_EMAIL) {
		return parent::getAdvanceTime($type);
	}



	/**
	 * Check whether email reminding for this event/person is disabled
	 *
	 * @return	boolean
	 */
	public function isDisabled($reminderType) {
		return parent::isDisabled($reminderType);
	}



	/**
	 * Send reminder as email
	 *
	 * @return	boolean
	 */
	public function sendAsEmail() {
		$event		= $this->getEvent();
		$person		= $this->getPerson();

			// Don't send when event or person's email is missing
		if( $event->isDeleted() || ! $person->hasEmail() ) {
			return false;
		}

		$mail		= new TodoyuCalendarReminderEmailMail($this->getID());
		$sendStatus	= $mail->send();

		if( $sendStatus ) {
			$this->saveAsSent();
		}

		return $sendStatus;
	}



	/**
	 * Set "is_sent" flag of reminder true, store
	 */
	private function saveAsSent() {
		$idReminder	= $this->getID();
		$idReceiver	= $this->getPersonID();

			// Set "is_sent"-flag in ext_calendar_mm_event_person
		TodoyuCalendarReminderManager::updateReminder($idReminder, array(
			'is_remindemailsent'	=> 1
		));

			// Save log record about sent mail
		$receiverTuples	= array($idReceiver);
		TodoyuMailManager::saveMailsSent(EXTID_CALENDAR, CALENDAR_TYPE_EVENTREMINDER_EMAIL, $idReminder, $receiverTuples);
	}

}

?>
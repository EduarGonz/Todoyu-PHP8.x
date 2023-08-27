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
 * Manage event mail DB logs
 *
 * @package		Todoyu
 * @subpackage	Calendar
 */
class TodoyuCalendarEventMailManager {

	/**
	 * Get IDs of users which are also assigned to the event (all except current user)
	 *
	 * @param	integer		$idEvent
	 * @return	integer[]
	 */
	public static function getOtherAssignedUserIDs($idEvent) {
		$idEvent	= intval($idEvent);

			// Get all assigned users
		$assignedPersonsWithEmail	= TodoyuCalendarEventStaticManager::getEmailReceivers($idEvent, false);
			// Remove current user
		unset($assignedPersonsWithEmail[Todoyu::personid()]);
			// Get user IDs
		return TodoyuArray::getColumn($assignedPersonsWithEmail, 'id_person');
	}



	/**
	 * Remove auto mail fieldset, if no users are receiving auto mail info
	 *
	 * @param	TodoyuForm		$form
	 * @param	integer			$idEvent
	 * @param	array			$params
	 * @return	TodoyuForm
	 */
	public static function hookToggleAutoMailField(TodoyuForm $form, $idEvent, array $params) {
		$autoEmailPersons	= self::getAutoNotifiedPersonIDs($idEvent);

		if( empty($autoEmailPersons) ) {
			$form->removeFieldset('autoemail');
		}

		return $form;
	}





	/**
	 * Get person IDs of participants receiving auto-notification event emails
	 *
	 * @param	integer		$idEvent
	 * @param	boolean		$ignoreCurrentUser
	 * @return	integer[]
	 */
	public static function getAutoNotifiedPersonIDs($idEvent, $ignoreCurrentUser = true) {
		$idEvent			= intval($idEvent);
		$event				= TodoyuCalendarEventStaticManager::getEvent($idEvent);
		$autoMailRoleIDs	= TodoyuCalendarManager::getAutoMailRoleIDs();
		$notifiedPersonIDs	= array();

		if( !empty($autoMailRoleIDs)  ) {
			$assignedPersons= $event->getAssignedPersons();

			foreach($assignedPersons as $assignedPerson) {
				if( $assignedPerson->hasAnyRole($autoMailRoleIDs) && $assignedPerson->hasAccountEmail() ) {
					$notifiedPersonIDs[] = $assignedPerson->getID();
				}
			}
		}

		if( $ignoreCurrentUser ) {
			$notifiedPersonIDs = TodoyuArray::removeByValue($notifiedPersonIDs, array(Todoyu::personid()));
		}

		return $notifiedPersonIDs;
	}



	/**
	 * Extract person IDs from list which are auto notified by mail
	 *
	 * @param	array	$personIDs
	 * @param	boolean	$ignoreCurrentUser
	 * @return	integer[]
	 */
	public static function extractAutoNotifiedPersonIDs(array $personIDs, $ignoreCurrentUser = true) {
		$personIDs			= TodoyuArray::intval($personIDs, true, true);
		$notifiedPersonIDs	= array();
		$autoMailRoleIDs	= TodoyuCalendarManager::getAutoMailRoleIDs();

		foreach($personIDs as $idPerson) {
			$person	= TodoyuContactPersonManager::getPerson($idPerson);

			if( $person->hasAnyRole($autoMailRoleIDs) ) {
				$notifiedPersonIDs[] = $idPerson;
			}
		}

		if( $ignoreCurrentUser ) {
			$notifiedPersonIDs = TodoyuArray::removeByValue($notifiedPersonIDs, array(Todoyu::personid()));
		}

		return $notifiedPersonIDs;
	}



	/**
	 * @param	integer							$idEvent
	 * @param	TodoyuMailReceiverInterface		$mailReceiver
	 */
	public static function saveMailSent($idEvent, TodoyuMailReceiverInterface $mailReceiver) {
		$idReceiver		= $mailReceiver->getRecordID();
		$receiverType	= $mailReceiver->getType();

		TodoyuMailManager::addMailSent(EXTID_COMMENT, COMMENT_TYPE_COMMENT, $idEvent, $idReceiver, $receiverType);
	}



	/**
	 * Log sent event email of given event to given person
	 *
	 * @param	integer		$idEvent
	 * @param	integer		$idPerson
	 */
	public static function addMailSent($idEvent, $idPerson) {
		TodoyuMailManager::addMailSent(EXTID_CALENDAR, CALENDAR_TYPE_EVENT, $idEvent, $idPerson);
	}



	/**
	 * Get mail receivers the given event has been sent to by email
	 *
	 * @param	integer					$idEvent
	 * @return	TodoyuMailReceiverInterface[]
	 */
	public static function getEmailReceivers($idEvent) {
		return TodoyuMailManager::getEmailReceivers(EXTID_CALENDAR, CALENDAR_TYPE_EVENT, $idEvent);
	}



	/**
	 * Get event mail subject label by operation ID (create, update, delete)
	 *
	 * @param	string		$operation
	 * @param	boolean		$isSeriesAction
	 * @return	string
	 */
	public static function getEventMailSubject($operation, $isSeriesAction) {
		$operation	= trim($operation);

		return Todoyu::Label('calendar.event.mail.popup.subject.' . $operation);
	}



	/**
	 * Get data array to render event email
	 *
	 * @param	integer		$idEvent
	 * @param	integer		$idPersonMailTo
	 * @param	boolean		$isSentBySystem
	 * @param	integer		$idPersonSender
	 * @return	Array
	 */
	public static function getMailData($idEvent, $idPersonMailTo, $isSentBySystem = false, $idPersonSender = 0) {
		$idEvent		= intval($idEvent);
		$idPersonMailTo	= intval($idPersonMailTo);
		$idPersonSender	= Todoyu::personid($idPersonSender);

		$event			= TodoyuCalendarEventStaticManager::getEvent($idEvent, true);

		$data	= array(
			'event'			=> $event->getTemplateData(),
			'personReceive'	=> TodoyuContactPersonManager::getPerson($idPersonMailTo)->getTemplateData(),
			'personSend'	=> self::getPersonSendTemplateData($idPersonSender, $isSentBySystem),
			'personWrite'	=> self::getPersonWriteTemplateData($event),
			'attendees'		=> TodoyuCalendarEventStaticManager::getAssignedPersonsOfEvent($idEvent, true)
		);

		$urlParams	= array(
			'ext'	=> 'calendar',
			'event'	=> $idEvent,
			'tab'	=> 'view'
		);
		$data['eventlink']	= TodoyuString::buildUrl($urlParams, '', true);

		return $data;
	}



	/**
	 * Get event email sender person template data
	 *
	 * @param	integer		$idPersonSender
	 * @param	boolean		$isSentBySystem			Automatically sent, not by a person?
	 * @return	Array
	 */
	public static function getPersonSendTemplateData($idPersonSender, $isSentBySystem = false) {
		if( $isSentBySystem ) {
			return array(
				'firstname'	=> Todoyu::$CONFIG['SYSTEM']['name']
			);
		}

		return TodoyuAuth::getPerson($idPersonSender)->getTemplateData();
	}



	/**
	 * Get event email sender person template data
	 *
	 * @param	TodoyuCalendarEventStatic		$event
	 * @return	Array
	 */
	public static function getPersonWriteTemplateData(TodoyuCalendarEventStatic $event) {
		$personWrite	= $event->getPersonCreate();

		if( $personWrite !== false ) {
			return $personWrite->getTemplateData();
		}

		 return array();
	}



	/**
	 * Get person IDs of participants who are being auto-notified about event changes/creations
	 *
	 * @param	array	$participantIDs
	 * @return	Array|Integer[]
	 */
	public static function getAutoNotifiedPersonIDsOLD($participantIDs = array()) {
		$autoMailPersonIDs	= array();

		if( !empty($participantIDs)  ) {
			$participantIDs		= TodoyuArray::intval($participantIDs);

				// Get preset roles
			$autoMailRoles	= TodoyuSysmanagerExtConfManager::getExtConfValue('calendar', 'autosendeventmail');

			if( ! empty($autoMailRoles) ) {
					// Get person IDs of roles
				$autoMailRoles	= TodoyuArray::intExplode(',', $autoMailRoles);
				foreach($autoMailRoles as $idRole) {
					$autoMailPersonIDs	= array_merge($autoMailPersonIDs, TodoyuRoleManager::getPersonIDs($idRole));
				}
				$autoMailPersonIDs	= TodoyuArray::intval($autoMailPersonIDs);

					// Reduce to event participants
				$autoMailPersonIDs	= array_intersect($autoMailPersonIDs, $participantIDs);

					// Sort persons alphabetically
				if( !empty($autoMailPersonIDs)  ) {
					$autoMailPersonIDs	= TodoyuContactPersonManager::sortPersonIDs($autoMailPersonIDs);
				}
			}
		}

		return TodoyuArray::removeByValue($autoMailPersonIDs, array(Todoyu::personid()));
	}



	/**
	 * Hook for event moving
	 *
	 * @param	integer		$idEvent
	 * @param	integer		$dateStart
	 * @param	integer		$dateEnd
	 */
	public static function hookEventMoved($idEvent, $dateStart, $dateEnd) {
		self::sendAutoInfoMails($idEvent, array('new'=>false));
	}



	/**
	 * Hook for event saving. Send auto info mails to special group users
	 *
	 * @param	integer		$idEvent
	 * @param	array		$options
	 */
	public static function hookEventSaved($idEvent, array $options = array()) {
		$options['operation'] = $options['new'] ? 'create' : 'update';

		if( !$options['batch'] ) {
			self::sendAutoInfoMails($idEvent, $options);
		}
	}



	/**
	 * Hook for event delete
	 *
	 * @param	integer		$idEvent
	 * @param	array		$options
	 */
	public static function hookEventDeleted($idEvent, array $options = array()) {
		$options['operation'] = 'delete';

			// Don't send mails on batch delete
		if( !$options['batch'] ) {
			self::sendAutoInfoMails($idEvent, $options);
		}
	}



	/**
	 * Send info mails to all assigned users of the event which are in the specified groups
	 *
	 * @param	integer		$idEvent
	 * @param	array		$options
	 * @return	integer[]
	 */
	public static function sendAutoInfoMails($idEvent, array $options = array()) {
		$autoMailUserIDs = self::getAutoNotifiedPersonIDs($idEvent, true);

		if( !empty($autoMailUserIDs) ) {
			self::sendEvent($idEvent, $autoMailUserIDs, $options);
		}

		return $autoMailUserIDs;
	}



	/**
	 * Event save hook. Send emails
	 *
	 * @param	integer		$idEvent
	 * @param	string[]	$receiverTuples
	 * @param	array		$options
	 * @return	boolean
	 */
	public static function sendEvent($idEvent, array $receiverTuples, array $options = array()) {
		$receiverTuples	= TodoyuArray::trim($receiverTuples);
		$sent			= false;

		if( !empty($receiverTuples) ) {
			$sent	= self::sendEmails($idEvent, $receiverTuples, $options);
		}

		return $sent;
	}



	/**
	 * Send event information email to the persons
	 *
	 * @param	integer		$idEvent
	 * @param	array		$receiverTuples		'type:ID' or just 'ID', which defaults the type to 'contactperson'
	 * @param	array		$options
	 * @return	boolean
	 */
	public static function sendEmails($idEvent, array $receiverTuples, array $options = array()) {
		$idEvent		= intval($idEvent);
		$receiverTuples	= TodoyuArray::trim($receiverTuples, true, true);
		$mailReceivers	= TodoyuMailReceiverManager::getMailReceivers($receiverTuples);

		$succeeded	= true;
		foreach($mailReceivers as $mailReceiver) {
			$result	= self::sendInfoMail($idEvent, $mailReceiver, $options);

			if( !$result ) {
				$succeeded	= false;
			}
		}

		return $succeeded;
	}



	/**
	 * Send an event email to a person, log sent email.
	 *
	 * @param	integer							$idEvent
	 * @param	TodoyuMailReceiverInterface		$mailReceiver
	 * @param	array							$options
	 * @return	boolean							Success
	 */
	public static function sendInfoMail($idEvent, $mailReceiver, array $options = array()) {
		$idEvent		= intval($idEvent);

		$mail		= new TodoyuCalendarEventInfoEmail($idEvent, $mailReceiver, $options);
		$isSent		= $mail->send();

		if( $isSent ) {
			TodoyuCalendarEventMailManager::saveMailSent($idEvent, $mailReceiver);
		}

		TodoyuHookManager::callHook('calendar', 'email.info', array($idEvent, $mailReceiver, $options, $isSent));

		return $isSent;
	}

}

?>
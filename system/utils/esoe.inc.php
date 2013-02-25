<?php
/**
 * ESOE Authentication Library
 *
 * Pull all relevant roles from the SPEP session keys.  Abstract out names of the items so that they can be
 * easily changed in the future.
 *
 * @author Joseph Stewart
 * @author Matthew Kerwin
 * @copyright Copyright (c) 2012, QUT Library eServices
 * @category Authentication
 * @link https://github.com/esoeproject
 * @link http://www.library.qut.edu.au 
 * @version 1.2
 * @filesource
 */

/**
 * ESOE Authentication Library
 *
 * @package EzproxyTicketing 
 */
class ESOE {

	/**
	 * Creates a new TemplateEngine, populated with some cool ESOE items.
	 *
	 *   %%esoe.username%%
	 *   %%esoe.email%%
	 *   %%esoe.surname%%
	 *   %%esoe.givenname%%
	 *   %%esoe.name%%
	 */
	public static function template($filename=NULL) {
		$te = new TemplateEngine($filename);
		$te->set('esoe.username',  self::username());
		$te->set('esoe.email',     self::email());
		$te->set('esoe.surname',   self::surname());
		$te->set('esoe.givenname', self::givenName());
		$te->set('esoe.name',      self::surname().', '.self::givenName());
		return $te;
	}

    /**
     * Return a value from the server session variables. If an unknown session variable was
     * requested, log the error and return a null. 
     *
     * @param string $varName Name of array element to be pulled from $_SERVER session variable.
     * @param string|integer $fallback Value to ruturn to calling procedure if the requested element cannot be found.
     * @return string Content of array element in $_SERVER session variable, or $fallback if not found.
     */
    private static function getServerVar($varName, $fallback=null) {
        $varContent = null;
        if (array_key_exists($varName, $_SERVER)) {
            $varContent = $_SERVER[$varName];
        } elseif (array_key_exists('REDIRECT_'.$varName, $_SERVER)) {
            $varContent = $_SERVER['REDIRECT_'.$varName];
        } else {
            #error_log("Invalid session variable requested: '$varName'");
            $varContent = $fallback;
        }
        return $varContent; 
    }

    /**
     * Extended Logging
     *
     * Log messages using extended information from esoe.
     *
     * @param string $message Message to log to system logs.
     * @param string $level Error level to log message to.  Defaults to 'error'.
     */
    public static function log($message, $level='error') {
        $userCred = self::username().":".$_SERVER['REMOTE_ADDR'];
        error_log("[$level] $userCred - $message");
    }

    /**
     * Return the full qut username of the current user.
     *
     * @return string QUT username, or null if current user is not authenticated.
     */
    public static function username($fallback=NULL) {
        return self::getServerVar('SPEP_ATTR_uid', $fallback);
    }

    /**
     * Lookup the client id of the current user.
     *
     * @return integer ClientID of the current user, or 0 if current user is not authenticated.
     */
    public static function clientid() {
        return (int) self::getServerVar('SPEP_ATTR_qutEduAuClientId',0);
    }

    /**
     * Lookup the list of active directory groups that the user is a member of.
     *
     * @return string List of AD groups that the current user is a member of seperated by SPEP defined character, or null if not authenticated. 
     */
    static function groups($fallback=NULL) {
        return self::getServerVar('SPEP_ATTR_qutEduAuGroupMembership', $fallback);
    }

    /**
     * Lookup a list of roles that the current user is a member of.
     *
     * @return string Pipe seperated list of roles that the current user is a member of, or null if not authenticated.
     */
    static function roles($fallback=NULL) {
        return self::getServerVar('SPEP_ATTR_qutEduAuRole', $fallback);
    }

    /**
     * Lookup a list of services that the current user has access to.
     *
     * @return string Pipe seperated list of services that have been assgined to the user, or null if not authenticated.
     */
    static function services($fallback=NULL) {
        return self::getServerVar('SPEP_ATTR_qutEduAuServices', $fallback);
    }
   
    /**
     * Lookup the title that has been assigned to the current user in QUT Active Directory.
     *
     * @return string Full job title as specified in QUT Active Directory. 
     */
    static function title($fallback=NULL) {
        return self::getServerVar('SPEP_ATTR_title', $fallback);
    }

    /**
     * Lookup the telephone number that has been assigned to the user in QUT Active Directory.
     *
     * @return string Telephone number that is assigned to the current user in QUT Active Directory.
     */
    static function telephone($fallback=NULL) {
        return self::getServerVar('SPEP_ATTR_telephoneNumber', $fallback);
    }

    /**
     * Get the surname of the current user that is logged into QUT ESEO.
     *
     * @return string Surname of the current user.
     */
    static function surname($fallback=NULL) {
        return self::getServerVar('SPEP_ATTR_sn', $fallback);
    }

    /**
     * Lookup the room number of the current logged in user.  Mainly only of use for staff members.
     *
     * @return string Room number that the current user users for their office.
     */
    static function roomnumber($fallback=NULL) {
        return self::getServerVar('SPEP_ATTR_roomNumber', $fallback);
    }

    /**
     * Lookup the student number of the current user.
     *
     * @return string Student number of the current user.
     */
    static function studentNumber($fallback=NULL) {
        return self::getServerVar('SPEP_ATTR_qutEduAuStudentNumber', $fallback);
    }

    /**
     * Lookup the staff number of the current user.
     *
     * @return string Staff number of the currently logged in user.
     */
    static function staffNumber($fallback=NULL) {
        return self::getServerVar('SPEP_ATTR_qutEduAuStaffNumber', $fallback);
    } 

    /**
     * Lookup the date that this user accepted the university's rules.
     *
     * @return integer Timestamp of the date that the current user accepted the university's IT rules.
     */
    static function acceptedRules($fallback) {
		$val = self::getServerVar('SPEP_ATTR_qutEduAuAcceptedRules', NULL);
		if (is_null($val)) {
			return $fallback;
		}
        return strtotime($val);
    }

    /**
     * Lookup the Organisational Unit from Active Directory, that this user belongs to. 
     * Return value is in a pipe seperated list.
     *
     * @return string Pipe seperated list of OU's straight from active directory, or null if the OU cannot be found.
     */
    static function ou($fallback=NULL) {
        return self::getServerVar('SPEP_ATTR_ou', $fallback);
    }

    /**
     * Lookup the mobile number of the currently logged in user.
     *
     * @return string Mobile phone number of the active user, or null if the user has not set a mobile number.
     */
    static function mobile($fallback=NULL) {
        return self::getServerVar('SPEP_ATTR_mobile', $fallback);
    }

    /**
     * Lookup the email address of the currently logged in user.
     * 
     * @return string Email address of the active user, or null if the user does not have an email address.
     */
    static function email($fallback=NULL) {
        return self::getServerVar('SPEP_ATTR_mail', $fallback);
    }

    /**
     * Lookup the hosted email username for the current user.  Used to retrieve username that is in 
     * use for the Microsoft hosted email service at QUT.
     *
     * @return string Connect @ QUT username of the current user.
     */
    static function connectUsername($fallback=NULL) {
        return self::getServerVar('SPEP_ATTR_liveAtEduUsername', $fallback);
    }

    /**
     * Lookup the hosted email status for the current user.  Most staff accounts will have this as null.
     *
     * @return string Connect @ QUT status for the current user.
     */
    static function connectStatus($fallback=NULL) {
        return self::getServerVar('SPEP_ATTR_liveAtEduStatus', $fallback);
    }

    /**
     * Lookup the current user's initials from SPEP.
     *
     * @return string Initials of the current user, excluding the surname.
     */
    static function initials($fallback=NULL) {
        return self::getServerVar('SPEP_ATTR_initials', $fallback);
    }

    /**
     * Lookup the given name of the current user.
     * 
     * @return string Given name of the current user.
     */
    static function givenName($fallback=NULL) {
        return self::getServerVar('SPEP_ATTR_givenName', $fallback);
    }

    /**
     * Lookup the fax number that is assigned to the current user.  Mainly useful for staff accounts only.
     *
     * @return string Fax number that can be used to reach the current user.
     */
    static function fax($fallback=NULL) {
        return self::getServerVar('SPEP_ATTR_facsimileTelephoneNumber', $fallback);
    }

    /**
     * Lookup the principal name for the currently logged in user.  This is usually the email address
     * of the logged in user.
     * 
     * @return string Principal name associated with the loggedn in user.
     */
    static function principalName($fallback=NULL) {
        return self::getServerVar('SPEP_ATTR_eduPersonPrincipalName', $fallback);
    }

    /** 
     * Lookup the description of the current user.  This will usually be the full name of the current user.
     *
     * @return string Full description of the current user as retrieved from QUT Directories.
     */
    static function description($fallback=NULL) {
        return self::getServerVar('SPEP_ATTR_description', $fallback);
    }

    /**
     * Lookup the canonical name of the current user.  This will usually be the full name of the current user.
     *
     * @return string CN of current user.
     */
    static function cn($fallback=NULL) {
        return self::getServerVar('SPEP_ATTR_CN', $fallback);
    }

}


<?php
/**
 * common.inc
 * 
 * This file contains system-specific parameters and configurations for the application.
 * It defines paths for user-configurable files, log files, and various command-line utilities
 * used throughout the application. It also checks for the existence of certain files and sets
 * flags accordingly.
 */

/**
 * Location of all user configurable files
 */
$USERFILES = "user_files";

/**
 * Path/name of Supermon2 login/out log
 */
$SMLOGNAME = "/tmp/SMLOG.txt";

/**
 * Title in header when logged in
 */
$TITLE_LOGGED = "Supermon2 ASL3+";
$VERSION_DATE = "April 29,2025";

/**
 * Title in header when not logged in
 */
$TITLE_NOT_LOGGED = "$TITLE_LOGGED";

/**
 * Webserver access log
 */
$WEB_ACCESS_LOG = "/var/log/apache2/access.log";

/**
 * Webserver error log
 */
$WEB_ERROR_LOG = "/var/log/apache2/error.log";

/**
 * Asterisk message log
 */
$ASTERISK_LOG = "/var/log/asterisk/messages.log";

/**
 * AllStar allmon database
 */
$ASTDB_TXT = "/var/www/html/supermon2/astdb.txt";

/**
 * AllStar rpt_extnodes file --- not present in DNS mode.
 */
$EXTNODES = "custom/rpt_extnodes";

/**
 * AllStar Echolink database dump
 */
$ECHO_DB = "/tmp/echolink.db.gz";

/**
 * Private AllStar nodelist
 */
$PRIVATENODES = "/var/www/html/supermon2/privatenodes.txt";

/**
 * IRLP related...
 */
$IRLP_CALLS = "/tmp/irlpdata.txt.gz";
$IRLP_LOG = "/home/irlp/log/messages";

/**
 * Cache version number info.
 */
$file = `find /tmp/asterisk_version /run/asterisk.pid /etc/allstar_version -printf "%T@  %p\n" 2>/dev/null | sort -nr | awk '{if (NR == 1) printf ("%s", $2)}'`;
if ($file == "/tmp/asterisk_version") {
    $version = `cat /tmp/asterisk_version`;
} else {
    $version = `export TERM=vt100; sudo asterisk -rx "core show version" | awk '{print $2}' | tee /tmp/asterisk_version`;
}

$version = "Asterisk $version";

/**
 * Make sure this is not hamvoip "Vx.xrc" code
 */
$pattern = "/ [0-9].[0-9]rc[0-9]/";
if (preg_match($pattern, $version, $match)) {
    $system_type = "allstarlink";
} else {
    $system_type = "allstarlink";
}

/**
 * Paths to various programs called from php...
 */
$ASTERISK = "/usr/bin/asterisk";
$AWK = "/usr/bin/awk";
$CAT = "/usr/bin/cat";
$CUT = "/usr/bin/cut";
$DATE = "/usr/bin/date";
$EGREP = "/usr/bin/egrep";
$GREP = "/usr/bin/grep";
$HEAD = "/usr/bin/head";
$HOSTNAME = "/usr/bin/hostname";
$IFCONFIG = "/usr/bin/ifconfig";
$JOURNALCTL = "/usr/bin/journalctl";
$MBUFFER = "";
$SED = "/usr/bin/sed";
$SUDO = "export TERM=vt100 && /usr/bin/sudo";
$TAIL = "/usr/bin/tail";
$UPTIME = "/usr/bin/uptime";
$WGET = "/usr/bin/wget";
$ZCAT = "/usr/bin/zcat";

/**
 * Check for the existence of IRLP log and calls files
 */
$IRLPLOG = file_exists($IRLP_LOG);
$IRLP = file_exists($IRLP_CALLS);
$EXTN = file_exists($EXTNODES);
?>

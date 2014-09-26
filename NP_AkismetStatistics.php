<?php

   /* ==========================================================================================
    * AkismetStatistics for Nucleus CMS
    * Copyright 2005-2007, Niels Leenheer
    * ==========================================================================================
    * This program is free software and open source software; you can redistribute
    * it and/or modify it under the terms of the GNU General Public License as
    * published by the Free Software Foundation; either version 2 of the License,
    * or (at your option) any later version.
    *
    * This program is distributed in the hope that it will be useful, but WITHOUT
    * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
    * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
    * more details.
    *
    * You should have received a copy of the GNU General Public License along
    * with this program; if not, write to the Free Software Foundation, Inc.,
    * 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA  or visit
    * http://www.gnu.org/licenses/gpl.html
    * ==========================================================================================
    */

class NP_AkismetStatistics extends NucleusPlugin {
	function getName()			{ return 'AkismetStatistics';}
	function getAuthor()  	  	{ return 'Niels Leenheer';}
	function getURL()  	  		{ return 'http://www.rakaz.nl';}
	function getVersion() 	  	{ return '0.2';}
	function getDescription() 	{ return 'Store statistics about the Akismet plugin';}
	function getEventList()     { return array('AkismetResult');}
	
	function supportsFeature($what) {return in_array($what,array('SqlTablePrefix'));}
	
	function doSkinVar($skinType, $what = '') {
		$tbl_plug_akismet_statistics = sql_table('plug_akismet_statistics');
		switch ($what) {
			case 'all':
				$query = sprintf('SELECT SUM(count) as sum FROM %s WHERE status=1',$tbl_plug_akismet_statistics);
				$res = sql_query($query);
				$row = sql_fetch_array($res);
				if ($row) echo (int) $row['sum'];
				else      echo '0';
				break;
			case 'today':
				$query = sprintf('SELECT SUM(count) as sum FROM %s WHERE status=1 AND day=NOW()',$tbl_plug_akismet_statistics);
				$res = sql_query($query);
				$row = sql_fetch_array($res);
				if ($row) echo (int) $row['sum'];
				else      echo '0';
				break;
			case 'percentage':
				$query = sprintf('SELECT SUM(count) as sum FROM %s WHERE status=1',$tbl_plug_akismet_statistics);
				$res = sql_query($query);
				$row = sql_fetch_array($res);
				if ($row)
				{
					$spam = (int) $row['sum'];
					$query = sprintf('SELECT SUM(count) as sum FROM %s WHERE status=0',$tbl_plug_akismet_statistics);
					$res = sql_query($query);
					$row = sql_fetch_array($res);
					if ($row)
					{
						$ham = (int) $row['sum'];
						echo round ( ($spam / ($ham + $spam)) * 100 ) . '%';
					}
					else
						echo '100%';
				}
				else
					echo '0%';
				break;
		}
	}
	
	function event_AkismetResult(&$data) {
		$vs = array(sql_table('plug_akismet_statistics'), addslashes($data['id']), (int) $data['status']);
		$query = vsprintf('SELECT * FROM %s WHERE id=%s AND day=NOW() AND status=%s', $vs);
		$res = sql_query($query);
		$row = sql_fetch_array($res);
		if ($row)
			$query = vsprintf('UPDATE %s SET count=count+1 WHERE id=%s AND day=NOW() AND status=%s', $vs);
		else 
			$query = vsprintf('INSERT INTO %s SET id=%s, day=NOW(), status=%s, count=1', $vs);
		sql_query($query);
	}
	
	function install() {
		$this->createOption('DropTable', 'Clear the database when uninstalling','yesno','no');

		@sql_query('
			CREATE TABLE 
				' . sql_table('plug_akismet_statistics') . ' 
			(
				id int(11),
				day date,
				status int(11),
				count int(11),
				UNIQUE KEY `id_day_status` (`id`,`day`,`status`)
			)
		');
	}

	function unInstall() {
		if ($this->getOption('DropTable') == 'yes') {
			sql_query('DROP TABLE ' . sql_table('plug_akismet_statistics'));
		}
	}
}

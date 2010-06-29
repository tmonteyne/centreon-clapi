<?php
/*
 * Copyright 2005-2010 MERETHIS
 * Centreon is developped by : Julien Mathis and Romain Le Merlus under
 * GPL Licence 2.0.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation ; either version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, see <http://www.gnu.org/licenses>.
 *
 * Linking this program statically or dynamically with other modules is making a
 * combined work based on this program. Thus, the terms and conditions of the GNU
 * General Public License cover the whole combination.
 *
 * As a special exception, the copyright holders of this program give MERETHIS
 * permission to link this program with independent modules to produce an executable,
 * regardless of the license terms of these independent modules, and to copy and
 * distribute the resulting executable under terms of MERETHIS choice, provided that
 * MERETHIS also meet, for each linked independent module, the terms  and conditions
 * of the license of that module. An independent module is a module which is not
 * derived from this program. If you modify this program, you may extend this
 * exception to your version of the program, but you are not obliged to do so. If you
 * do not wish to do so, delete this exception statement from your version.
 *
 * For more information : contact@centreon.com
 *
 * SVN : $URL: http://svn.modules.centreon.com/centreon-clapi/trunk/www/modules/centreon-clapi/core/class/centreonHost.class.php $
 * SVN : $Id: centreonHost.class.php 25 2010-03-30 05:52:19Z jmathis $
 *
 */
 
class CentreonHostCategory {
	private $DB;
	
	public function __construct($DB) {
		$this->DB = $DB;
	}

	/*
	 * Check host existance
	 */
	protected function hostCategoryExists($name) {
		if (!isset($name))
			return 0;
		
		/*
		 * Get informations
		 */
		$DBRESULT =& $this->DB->query("SELECT hc_name, hc_id FROM hostcategories WHERE hc_name = '".htmlentities($name, ENT_QUOTES)."'");
		if ($DBRESULT->numRows() >= 1) {
			$host =& $DBRESULT->fetchRow();
			$DBRESULT->free();
			return $host["hc_id"];
		} else {
			return 0;
		}
	}
	
	protected function checkParameters($options) {
		if (!isset($options) || $options == "") {
			print "No options defined. $str\n";
			$this->return_code = 1;
			return 1;
		}
	}
	
	protected function getHostCategoryID($hc_name = NULL) {
		if (!isset($hc_name))
			return;
			
		$request = "SELECT hc_id FROM hostcategories WHERE hc_name LIKE '$hc_name'";
		$DBRESULT =& $this->DB->query($request);
		$data =& $DBRESULT->fetchRow();
		return $data["hc_id"];
	}
	
	public function del($options) {
		
		$this->checkParameters($options);
		
		$request = "DELETE FROM hostcategories WHERE hc_name LIKE '".htmlentities($options, ENT_QUOTES)."'";
		$DBRESULT =& $this->DB->query($request);
		$this->return_code = 0;
		return;
	}
	
	public function show($search = NULL) {
		$searchStr = "";
		if (isset($search) && $search != "") {
			$searchStr = " WHERE hc_name LIKE '%".htmlentities($search, ENT_QUOTES)."%'";
		}
		$request = "SELECT hc_id, hc_name, hc_alias FROM hostcategories $searchStr ORDER BY hc_name";
		$DBRESULT =& $this->DB->query($request);
		$i = 0;
		while ($data =& $DBRESULT->fetchRow()) {
			if ($i == 0) {
				print "id;name;alias;members\n";
			}
			print $data["hc_id"].";".html_entity_decode($data["hc_name"], ENT_QUOTES).";".html_entity_decode($data["hc_alias"], ENT_QUOTES).";";
			
			$members = "";
			$request = "SELECT host_name FROM host, hostcategories_relation WHERE hostcategories_hc_id = '".$data["hc_id"]."' AND host_host_id = host_id ORDER BY host_name";
			$DBRESULT2 =& $this->DB->query($request);
			while ($m =& $DBRESULT2->fetchRow()) {
				if ($members != "") {
					$members .= ",";
				}
				$members .= $m["host_name"];				
			}
			$DBRESULT2->free();
			print $members."\n";
			
			$i++;
		}
		$DBRESULT->free();
	}

	/* *************************************
	 * Add functions
	 */
	public function add($options) {
		/*
		 * Split options
		 */
		$info = split(";", $options);

		if (!$this->hostCategoryExists($info[0])) {
			$convertionTable = array(0 => "hc_name", 1 => "hc_alias");
			$informations = array();
			foreach ($info as $key => $value) {
				$informations[$convertionTable[$key]] = $value;
			}
			$this->addHostCategory($informations);
			unset($informations);
		} else {
			print "Hostgroup ".$info[0]." already exists.\n";
			$this->return_code = 1;
			return;
		}
	}

	protected function addHostCategory($information) {
		if (!isset($information["hc_name"])) {
			print "No information received\n";
			return 0;
		} else {
			if (!isset($information["hc_alias"]) || $information["hc_alias"] == "")
				$information["hc_alias"] = $information["hc_name"];
			
			$request = "INSERT INTO hostcategories (hc_name, hc_alias, hc_activate) VALUES ('".htmlentities($information["hc_name"], ENT_QUOTES)."', '".htmlentities($information["hc_alias"], ENT_QUOTES)."', '1')";
			$DBRESULT =& $this->DB->query($request);
	
			$hc_id = $this->getHostCategoryID($information["hc_name"]);
			return $hc_id;
		}
	}
	
	/* ***************************************
	 * Set params
	 */
	public function setParam($options) {
		$elem = split(";", $options);
		return $this->setParamHostCategory($elem[0], $elem[1], $elem[2]);
	}
	
	protected function setParamHostCategory($hc_name, $parameter, $value) {
		
		$value = htmlentities($value, ENT_QUOTES);
		
		$hc_id = $this->getHostCategoryID($hc_name);
		if ($hc_id) {
			$request = "UPDATE hostcategories SET $parameter = '$value' WHERE hc_id = '$hc_id'";
			$DBRESULT =& $this->DB->query($request);
			if ($DBRESULT) {
				return 0;
			} else {
				return 1;
			}
		} else {
			print "Host category doesn't exists. Please check your arguments\n";
			return 1;	
		}
	}
	
	/* ************************************
	 * Add Child
	 */
	
	public function addChild($options) {
		$elem = split(";", $options);
		return $this->return_code = $this->addChildHostCategory($elem[0], $elem[1]);
	} 
	
	protected function addChildHostCategory($hc_name, $child) {
		
		require_once "./class/centreonHost.class.php";
		
		/*
		 * Get Child informations
		 */
		$host = new CentreonHost($this->DB, "HOST");
		$host_id = $host->getHostID(htmlentities($child, ENT_QUOTES));
		
		$hc_id = $this->getHostCategoryID($hc_name);
		if ($hc_id && $host_id) {
			$request = "DELETE FROM hostcategories_relation WHERE host_host_id = '$host_id' AND hostcategories_hc_id = '$hc_id'";
			$DBRESULT =& $this->DB->query($request);
			$request = "INSERT INTO hostcategories_relation (host_host_id, hostcategories_hc_id) VALUES ('$host_id', '$hc_id')";
			$DBRESULT =& $this->DB->query($request);
			if ($DBRESULT) {
				return 0;
			} else {
				return 1;
			}
		} else {
			print "Host category or host doesn't exists. Please check your arguments\n";
			return 1;	
		}
	}
	
	/* ************************************
	 * Del Child
	 */
	
	public function delChild($options) {
		$elem = split(";", $options);
		return $this->return_code = $this->delChildHostCategory($elem[0], $elem[1]);
	} 
	
	protected function delChildHostCategory($hc_name, $child) {
		
		require_once "./class/centreonHost.class.php";
		
		/*
		 * Get Child informations
		 */
		$host = new CentreonHost($this->DB, "HOST");
		$host_id = $host->getHostID(htmlentities($child, ENT_QUOTES));
		
		$hc_id = $this->getHostCategoryID($hc_name);
		if ($hc_id && $host_id) {
			$request = "DELETE FROM hostcategories_relation WHERE host_host_id = '$host_id' AND hostcategories_hc_id = '$hc_id'";
			$DBRESULT =& $this->DB->query($request);
			if ($DBRESULT) {
				return 0;
			} else {
				return 1;
			}
		} else {
			print "Host category or host doesn't exists. Please check your arguments\n";
			return 1;	
		}
	}
}
?>
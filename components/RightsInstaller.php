<?php
/**
* Rights installer component class file.
*
* @author Christoffer Niska <cniska@live.com>
* @copyright Copyright &copy; 2010 Christoffer Niska
* @since 0.9.3
*/
class RightsInstaller extends CApplicationComponent
{
	/**
	* @var CDbAuthManager
	*/
	private $_authManager;
	/**
	* @var CDbConnection
	*/
	public $db;
	/**
	* @var boolean whether Rights is installed or not.
	*/
	public $isInstalled;

	/**
	* Initializes the installer.
	*/
	public function init()
	{
		$this->_authManager = Yii::app()->getAuthManager();
		$this->db = $this->_authManager->db;
		$this->isInstalled = $this->isInstalled();

		parent::init();
	}

	/**
	* Runs the installer.
	* @param string the name of the super user role.
	* @param array the list of default roles.
	* @param array the list of super users to be assigned (id=>name).
	* @param boolean whether to drop and recreate the tables if they exist.
	* @return boolean whether the installer ran successfully or not.
	*/
	public function run($superUserRole, $defaultRoles, $superUsers, $overwrite)
	{
		// Run the installer only if the module is not already installed
		// or if we wish to overwrite the existing tables.
		if( $this->isInstalled===false || $overwrite===true )
		{
			$itemTable = $this->_authManager->itemTable;
			$itemChildTable = $this->_authManager->itemChildTable;
			$itemWeightTable = $this->_authManager->itemWeightTable;
			$assignmentTable = $this->_authManager->assignmentTable;

			// Start transaction
			$txn = $this->db->beginTransaction();

			try
			{
				// Drop tables if necessary
				if( $overwrite===true )
					$this->dropTables();

				// Create the AuthItem-table
				$sql = "create table {$itemTable} ( ";
				$sql.= "name varchar(64) not null, ";
				$sql.= "type integer not null, ";
				$sql.= "description text, ";
				$sql.= "bizrule text, ";
				$sql.= "data text, ";
				$sql.= "primary key (name) ";
				$sql.= ") type=InnoDB";
				$command = $this->db->createCommand($sql);
				$command->execute();

				// Create the AuthChild-table
				$sql = "create table {$itemChildTable} ( ";
				$sql.= "parent varchar(64) not null, ";
				$sql.= "child varchar(64) not null, ";
				$sql.= "primary key (parent, child), ";
				$sql.= "foreign key (parent) references {$itemTable} (name) on delete cascade on update cascade, ";
				$sql.= "foreign key (child) references {$itemTable} (name) on delete cascade on update cascade ";
				$sql.= ") type=InnoDB";
				$command = $this->db->createCommand($sql);
				$command->execute();

				// Create the AuthItemWeight-table
				$sql = "create table {$itemWeightTable} ( ";
				$sql.= "itemname varchar(64) not null, ";
				$sql.= "type integer not null, ";
				$sql.= "weight integer, ";
				$sql.= "primary key (itemname), ";
				$sql.= "foreign key (itemname) references {$itemTable} (name) on delete cascade on update cascade ";
				$sql.= ") type=InnoDB";
				$command = $this->db->createCommand($sql);
				$command->execute();

				// Create the AuthAssignment-table
				$sql = "create table {$assignmentTable} ( ";
				$sql.= "itemname varchar(64) not null, ";
				$sql.= "userid varchar(64) not null, ";
				$sql.= "bizrule text, ";
				$sql.= "data text, ";
				$sql.= "primary key (itemname, userid), ";
				$sql.= "foreign key (itemname) references {$itemTable} (name) on delete cascade on update cascade ";
				$sql.= ") type=InnoDB";
				$command = $this->db->createCommand($sql);
				$command->execute();

				// Insert the necessary roles
				$roles = array_merge(array($superUserRole), $defaultRoles);
				foreach( $roles as $roleName )
				{
					$sql = "insert into {$itemTable} (name, type, data) ";
					$sql.= "values (:name, :type, :data)";
					$command = $this->db->createCommand($sql);
					$command->bindValue(':name', $roleName);
					$command->bindValue(':type', CAuthItem::TYPE_ROLE);
					$command->bindValue(':data', 'N;');
					$command->execute();
				}

				// Assign the super users their role
				foreach( $superUsers as $id )
				{
					$sql = "insert into {$assignmentTable} (itemname, userid, data) ";
					$sql.= "values (:itemname, :userid, :data)";
					$command = $this->db->createCommand($sql);
					$command->bindValue(':itemname', $superUserRole);
					$command->bindValue(':userid', $id);
					$command->bindValue(':data', 'N;');
					$command->execute();
				}

				// All commands executed successfully, commit
				$txn->commit();
				return true;
			}
			catch( CDbException $e )
			{
				// Something went wrong, rollback
				$txn->rollback();
				return false;
			}
		}
	}

	/**
	* Drops the tables in the correct order.
	*/
	private function dropTables()
	{
		$sql = "drop table if exists {$this->_authManager->assignmentTable}";
		$command = $this->db->createCommand($sql);
		$command->execute();

		$sql = "drop table if exists {$this->_authManager->itemWeightTable}";
		$command = $this->db->createCommand($sql);
		$command->execute();

		$sql = "drop table if exists {$this->_authManager->itemChildTable}";
		$command = $this->db->createCommand($sql);
		$command->execute();

		$sql = "drop table if exists {$this->_authManager->itemTable}";
		$command = $this->db->createCommand($sql);
		$command->execute();
	}

	/**
	* @return boolean whether Rights is installed or not.
	*/
	public function isInstalled()
	{
		try
		{
			$sql = "SELECT COUNT(*) FROM {$this->_authManager->itemTable}";
			$command = $this->db->createCommand($sql);
			$command->queryScalar();

			$sql = "SELECT COUNT(*) FROM {$this->_authManager->itemChildTable}";
			$command = $this->db->createCommand($sql);
			$command->queryScalar();

			$sql = "SELECT COUNT(*) FROM {$this->_authManager->assignmentTable}";
			$command = $this->db->createCommand($sql);
			$command->queryScalar();

			return true;
		}
		catch( CDbException $e )
		{
			return false;
		}
	}
}

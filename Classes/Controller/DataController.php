<?php
namespace AshokaTree\Mobile\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/***
 *
 * This file is part of the "Mobile" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2020 Nirmalya Mondal <nirmalya.mondal@gmail.com>, https://ashokatree.net/
 *
 ***/

/**
 * DataController
 */
class DataController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * action sendReceive
     * 
     * @return void
     */
    public function sendReceiveAction()
    {
    	// Receive all requests
    	$authGp	= GeneralUtility::_GP('auth');
    	$username = GeneralUtility::_GP('username');
    	$data = GeneralUtility::_GP('data');
    	$taskid = GeneralUtility::_GP('taskid');
    	// Check Authentication
        if(!$this->auxAuthenticateRequest($authGp)) {
        	echo 'Sorry! Authentication failure! <br/> Call +91 75 85 87 22 25';
        	die();
        }        
        $userData = $this->auxUserAlreadyExist($username);
        $useruid = $userData['uid'];
        $customerName = $userData['name'];
        if(!$useruid) {
        	// Register New User & Show default content
        	$this->auxRegisterNewUser($username);

        }
        // Get products & taksks for this user
        $products	= $this->auxCustomerProducts($useruid);
        $tasks	= $this->auxCustomerTasks($useruid); 

        $categories = $this->auxAllCategoryBrandTypeStatusPriority('tx_management_domain_model_category');
        $brands = $this->auxAllCategoryBrandTypeStatusPriority('tx_management_domain_model_brand');
        $types = $this->auxAllCategoryBrandTypeStatusPriority('tx_management_domain_model_type');
        $statuses = $this->auxAllCategoryBrandTypeStatusPriority('tx_management_domain_model_status');
        $priorities = $this->auxAllCategoryBrandTypeStatusPriority('tx_management_domain_model_priority');
        $technicians = $this->auxAllTechnicians();

        //GET REQUESTS
        //dashboardScreen: Customer Dashboard View
        //Products
        if($data == 'dashboardProducts') {
        	$this->jsonDashboardProducts($products,$categories,$brands);
        	die();
        }
        //Tasks
        if($data == 'dashboardTasks') {
        	$this->jsonDashboardTasks($tasks,$products,$categories,$brands,$technicians);
        	die();
    	}
    	//detailScreen: Task Detail View
    	//Task Detail
    	if(($data == 'detailTask') && ($taskid != '')) {
        	$this->jsonDetailTask($tasks,$products,$categories,$brands,$technicians,$taskid);
        	die();
    	}
    	//Task Messages
    	if(($data == 'taskMessages') && ($taskid != '')) {
    		$messages	= $this->auxCustomerMessages($taskid);
        	$this->jsonTaskMessages($messages,$technicians,$customerName);
        	die();
    	}
    	//taskScreen: Task Add View
    	//Products of this User
    	if($data == 'newTaskProducts') {
        	$this->jsonNewTaskProducts($products,$categories,$brands);
        	die();
    	}
    	//Related issues for selection
    	if($data == 'newTaskIssues') {
        	$this->jsonNewTaskIssues($types);
        	die();
    	}
    	//profileScreen: User Profile Edit View
    	//User Data
        if($data == 'userDetail') {
        	echo json_encode($userData);
        	die();
        }
        
        //POST REQUESTS
    	//Auxiliary Data Post
       	//taskScreen: New Task - Add/ Submit
    	if($data == 'addNewTask') {
    		//Check if there is any limitation
        	$this->postAddNewTask();
        	die();
    	}
    	//detailScreen: New Message - Add/ Submit
    	if($data == 'addNewMessage') {
    		//Check if there is any limitation
        	$this->postAddNewMessage();
        	die();
    	}
    	//profileScreen: User Profile - Udate
    	if($data == 'updateProfile') {
        	$this->postUpdateProfile();
        	die();
    	}
    }

    /**
     * auxiliary function to authenticate request
     * 
     * @param string $array
     * @return int|null
     */
    public function auxAuthenticateRequest($authGp) {
        $authToken	= $this->settings['authToken'];
        if (strcmp($authToken, $authGp) !== 0) { 
		   return FALSE;
		}
    	return TRUE;
    }

    /**
     * auxiliary function customer dashboard products
     * 
     * @param string $array
     * @return json|null
     */
    public function jsonDashboardProducts($products,$categories,$brands) {
    	//print_r($categories);
    	$newProducts = [];
    	foreach($products as $key => $value) {
    		$newProducts[$key]['uid'] = $value['uid'];
    		$newProducts[$key]['product'] = $categories[$value['category']].' - '.$brands[$value['brand']].' : '.$value['serial'];
    		$newProducts[$key]['amc'] = date('d.m.Y', $value['amcexpire']);
    		$newProducts[$key]['invoice'] = $value['invoice'];
    	}
    	//echo json_encode($products);
    	echo json_encode($newProducts);
    }    

    /**
     * auxiliary function customer dashboard tasks
     * 
     * @param string $array
     * @return json|null
     */
    public function jsonDashboardTasks($tasks,$products,$categories,$brands,$technicians) {
    	//print_r($tasks);
    	$productArray = [];
		foreach($products as $pkey => $pvalue) {
			$productArray[$pvalue['uid']]['category'] = $pvalue['category'];
			$productArray[$pvalue['uid']]['brand'] = $pvalue['brand'];
			$productArray[$pvalue['uid']]['serial'] = $pvalue['serial'];
		}
    	$newTasks = [];
    	foreach($tasks as $key => $value) {
    		$productData = $productArray[$value['product']];
    		$newTasks[$key]['uid'] = $value['uid'];
    		$newTasks[$key]['product'] = $categories[$productData['category']].' - '.$brands[$productData['brand']].' : '.$productData['serial'];
    		$newTasks[$key]['technician'] = $technicians[$value['technician']]['username'];
    		$newTasks[$key]['dlink'] = 'https://google.com/index.php?id=1&pid=1';
    	}
    	//echo json_encode($tasks);
    	echo json_encode($newTasks);
    }  

    /**
     * auxiliary function customer detail task
     * 
     * @param string $array
     * @return json|null
     */
    public function jsonDetailTask($tasks,$products,$categories,$brands,$technicians,$taskid) {
    	$productArray = [];
		foreach($products as $pkey => $pvalue) {
			$productArray[$pvalue['uid']]['category'] = $pvalue['category'];
			$productArray[$pvalue['uid']]['brand'] = $pvalue['brand'];
			$productArray[$pvalue['uid']]['serial'] = $pvalue['serial'];
		}
    	$detailTask = [];
    	foreach($tasks as $key => $value) {
    		if($value['uid'] == $taskid) {
        		$productData = $productArray[$value['product']];
    			$detailTask[$key]['uid'] = $value['uid'];
    			$detailTask[$key]['product'] = $categories[$productData['category']].' - '.$brands[$productData['brand']].' : '.$productData['serial'];
    			$detailTask[$key]['technician'] = $technicians[$value['technician']]['name'];
    			$detailTask[$key]['tphone'] = $technicians[$value['technician']]['username'];
    			$detailTask[$key]['crdate'] = date('d.m.Y', $value['crdate']);
    			$detailTask[$key]['code'] = $value['passcode'];
    		}
    	}
    	echo json_encode($detailTask);
    }

    /**
     * auxiliary function for customer messages in task detail screen
     * 
     * @param string $array
     * @return json|null
     */
    public function jsonTaskMessages($messages,$technicians,$customerName) {
    	$newMessages = [];
    	foreach($messages as $key => $value) {
    		$technician = $technicians[$value['user']]['name'];
    		$newMessages[$key]['uid'] = $value['uid'];
    		$newMessages[$key]['name'] = $technician ? $technician : $customerName;
    		$newMessages[$key]['datetime'] = date('d.m.Y [h:i]', $value['datetime']);
    		$newMessages[$key]['comment'] = $value['message'];
    	}
    	echo json_encode($newMessages);
    } 

    /**
     * auxiliary function for customer products in new task screen
     * 
     * @param string $array
     * @return json|null
     */
    public function jsonNewTaskProducts($products,$categories,$brands) {
    	$newProducts = [];
    	foreach($products as $key => $value) {
    		$newProducts[][$value['uid']]= $categories[$value['category']].' - '.$brands[$value['brand']].' : '.$value['serial'];
    	}
    	echo json_encode($newProducts);
    } 

    /**
     * auxiliary function new task issues
     * 
     * @param string $array
     * @return json|null
     */
    public function jsonNewTaskIssues($issueTypes) {
    	$newIssueTypes = [];
    	foreach($issueTypes as $key => $value) {
    		$newIssueTypes[][$key]= $value;
    	}
    	echo json_encode($newIssueTypes);
    }

    /**
     * auxiliary function Add new task
     * 
     * @param string $array
     * @return true|false
     */
    public function postAddNewTask($useruid) {
    	$technicianuid = $this->settings['technicianUid'] ? $this->settings['technicianUid'] : 10;
    	$dbTable	= 'tx_management_domain_model_ticket';
		$insertDataArray = [
			'pid' => $pid,
			'tstamp' => time(),
			'crdate' => time(),
			'customer' => $useruid,
			'product' => $productuid,
			'technician' => $technicianuid,
			'type' => $typeuid,
			'priority' => $priorityuid,
			'status' => $statusuid,
			'place' => $placeuid,
			'name' => $name,
			'passcode' => mt_rand(100000,999999),
			'address' => $address,
			'note' => $note,
		];	
		//print_r($insertDataArray); die();			   
		$dbConnTicket = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($dbTable);
		$dbConnTicket->insert(
						$dbTable,
						$insertDataArray
					);
		return (int) $dbConnTicket->lastInsertId($dbTable);
    }

    /**
     * auxiliary function Add new message
     * 
     * @param string $array
     * @return true|false
     */
    public function postAddNewMessage() {
    	if(!$ticketuid || !$useruid || !$message) { return FALSE; }
    	$pid = $this->settings['messagePid'] ? $this->settings['messagePid'] : 25;
    	$dbTable	= 'tx_management_domain_model_message';
		$insertDataArray = [
			'pid' => $pid,
			'tstamp' => time(),
			'crdate' => time(),
			'ticket' => $ticketuid,
			'user' => $useruid,
			'message' => $message,
			'date' => time(),
		];	
		//print_r($insertDataArray); die();			   
		$dbConnMessage = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($dbTable);
		$dbConnMessage->insert(
						$dbTable,
						$insertDataArray
					);
		return (int) $dbConnMessage->lastInsertId($dbTable);
    }

    /**
     * auxiliary function Update profile
     * 
     * @param string $array
     * @return true|false
     */
    public function postUpdateProfile($username) {
    	if(!$username || !$name) { return FALSE; }
		$queryBuilderUp = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_users');
		$queryBuilderUp->update('fe_users')
						   ->where(
							  $queryBuilderUp->expr()->eq('username', $queryBuilderUp->createNamedParameter($username, \PDO::PARAM_STR))
						   )
						   ->set('name', $name)
						   ->set('address', $address)
						   ->set('email', $email)
						   ->set('zip', $zip)
						   ->set('telephone', $telephone)
						   ->set('lastlogin', time())
						   ->execute();
    }

    /**
     * auxiliary function
     * 
     * @param string $username
     * @return int|null
     */
    public function auxUserAlreadyExist($username) {
    	if(!$username) { return FALSE; }
        $queryBuilder   = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_users');
        $statement      = $queryBuilder
                               ->select('uid','name','address','email','zip','telephone','crdate')
                               ->from('fe_users')
                               ->where($queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($username, \PDO::PARAM_STR)))
                               ->execute();
        $dataRow    = $statement->fetch();
        return $dataRow;
    }

    /**
     * auxiliary function to register a new User | Mobile
     * 
     * @param string $username
     * @return int|null
     */
    public function auxRegisterNewUser($username) {
    	if(!$username) { return FALSE; }
    	$password = $this->settings['defaultpwd'] ? $this->settings['defaultpwd'] : 'nirmalya143';
    	$usergroup = $this->settings['usergroup'] ? $this->settings['usergroup'] : 1;
    	$pid = $this->settings['customerPid'] ? $this->settings['customerPid'] : 2;
    	$dbTable	= 'fe_users';
		$insertDataArray = [
			'pid' => $pid,
			'tstamp' => time(),
			'crdate' => time(),
			'username' => $username,
			'usergroup' => $usergroup,
			'password' => $password,
		];	
		//print_r($insertDataArray); die();			   
		$dbConnMessage = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($dbTable);
		$dbConnMessage->insert(
						$dbTable,
						$insertDataArray
					);
		return (int) $dbConnMessage->lastInsertId($dbTable);
    }

    /**
     * auxiliary function to get all products of a User
     * 
     * @param string $useruid
     * @return int|null
     */
    public function auxCustomerProducts($useruid) {
    	if(!$useruid) { return FALSE; }
    	$qbProd = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_management_domain_model_product');
		$aufProd = $qbProd->select('uid','invoice','amcexpire','serial','category','brand')->from('tx_management_domain_model_product')->where($qbProd->expr()->eq('customer',$useruid))
							   ->addOrderBy('uid', 'DESC')
							   ->setMaxResults(100)		   
							   ->execute();
	return $aufProd->fetchAll();
    }

    /**
     * auxiliary function to get all tasks of a User
     * 
     * @param string $useruid
     * @return int|null
     */
    public function auxCustomerTasks($useruid) {
    	if(!$useruid) { return FALSE; }
    	$qbTask = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_management_domain_model_ticket');
		$aufTask = $qbTask->select('uid','product','technician','type','priority','status','place','name','passcode','address','note','crdate')->from('tx_management_domain_model_ticket')->where($qbTask->expr()->eq('customer',$useruid))
							   ->addOrderBy('uid', 'ASC')
							   ->setMaxResults(100)		   
							   ->execute();
	return $aufTask->fetchAll();
    }

    /**
     * auxiliary function to get all tasks of a User
     * 
     * @param string $useruid
     * @return int|null
     */
    public function auxCustomerMessages($ticket) {
    	if(!$ticket) { return FALSE; }
    	$qbProd = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_management_domain_model_message');
		$aufProd = $qbProd->select('uid','user','message','date')->from('tx_management_domain_model_message')->where($qbProd->expr()->eq('ticket',$ticket))
							   ->addOrderBy('uid', 'DESC')
							   ->setMaxResults(100)		   
							   ->execute();
	return $aufProd->fetchAll();
    }
    
    /**
     * auxiliary function to get all category, brand, type, status, priority 
     * 
     * @param string $useruid
     * @return int|null
     */
    public function auxAllCategoryBrandTypeStatusPriority($table) {
    	if(!$table) { return FALSE; }
    	$qbProd = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
		//$aufProd = $qbProd->select('uid','name')->from($table)->where($qbProd->expr()->eq('ticket',$ticket))
		$aufProd = $qbProd->select('uid','name')->from($table)
							   ->addOrderBy('uid', 'ASC')
							   ->setMaxResults(100)		   
							   ->execute();
		$allDataArray = $aufProd->fetchAll();
		$dataArray = [];
		foreach($allDataArray as $key => $value) {
			$dataArray[$value['uid']] = $value['name'];
		}
		return $dataArray;
    }
    
    /**
     * auxiliary function to get all technicians 
     * 
     * @param string $useruid
     * @return int|null
     */
    public function auxAllTechnicians() {
    	$qbProd = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_users');
		$aufProd = $qbProd->select('uid','username','name')->from('fe_users')->where($qbProd->expr()->eq('pid',(int)$this->settings['technicianPid']))
							   ->addOrderBy('uid', 'ASC')
							   ->setMaxResults(100)		   
							   ->execute();
		$allDataArray = $aufProd->fetchAll();
		$dataArray = [];
		foreach($allDataArray as $key => $value) {
			$dataArray[$value['uid']]['username'] = $value['username'];
			$dataArray[$value['uid']]['name'] = $value['name'];
		}
		return $dataArray;
    }

}

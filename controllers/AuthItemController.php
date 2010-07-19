<?php
/**
* Rights authorization item controller class file.
*
* @author Christoffer Niska <cniska@live.com>
* @copyright Copyright &copy; 2010 Christoffer Niska
* @since 0.5
*/
class AuthItemController extends Controller
{
	/**
	* @var RightsModule
	*/
	private $_module;

	/**
	* @var RightsAuthorizer
	*/
	private $_auth;

	/**
	 * @var CAuthItem the currently loaded data model instance.
	 */
	private $_model;

	/**
	* Initialization.
	*/
	public function init()
	{
		$this->defaultAction = 'create';
		$this->_module = Yii::app()->getModule('rights');
		$this->_auth = $this->_module->getComponent('auth');
	}

	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
			'accessControl',
		);
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
			array('allow',
				'actions'=>array(
					'create',
					'update',
					'delete',
					'removeChild',
					'assign',
					'revoke',
				),
				'users'=>array($this->_auth->superUser),
			),
			array('deny',
				'users'=>array('*'),
			),
		);
	}

	/**
	* Creates an authorization item.
	*/
	public function actionCreate()
	{
		// Create the auth item form
	    $form = new CForm('application.modules.rights.config.authItemForm', new AuthItemForm);
	    $form->scenario = 'create';

	    // Form is submitted and data is valid, redirect the user
	    if( $form->submitted()===true && $form->validate()===true )
		{
			// Create and redirect
			$this->_auth->createAuthItem($form->model->name, $form->model->type, $form->model->description, $form->model->bizRule, $form->model->data);
			$this->redirect(array('authItem/update', 'name'=>$form->model->name));
		}

		// Render the view
		$this->render('create', array(
			'form'=>$form,
		));
	}

	/**
	* Updates an authorization item.
	*/
	public function actionUpdate()
	{
		// Get the authorization item
		$model = $this->loadModel();

		// Create the auth item form
	    $form = new CForm('application.modules.rights.config.authItemForm', new AuthItemForm);
	    $form->scenario = 'update';
	    unset($form->elements['type']); // Unset the type as it cannot be changed

		// Create a from to add a child for the auth item
		$childrenSelectOptions = $this->_auth->getAuthItemSelectOptions($model->type, $model);
	    $childForm = new CForm(array(
		    'elements'=>array(
		        'child'=>array(
		            'type'=>'dropdownlist',
		            'items'=>$childrenSelectOptions,
		        ),
		    ),
		    'buttons'=>array(
		        'submit'=>array(
		            'type'=>'submit',
		            'label'=>Yii::t('rights', 'Add'),
		        ),
		    ),
		), new ChildForm);

	    // Form is submitted and data is valid, redirect the user
	    if( $form->submitted()===true && $form->validate()===true )
		{
			// Update and redirect
			$this->_auth->updateAuthItem($_GET['name'], $form->model->name, $form->model->description, $form->model->bizRule, $form->model->data);
			$this->redirect(array(isset($_GET['redirect'])===true ? urldecode($_GET['redirect']) : 'main/permissions'));
		}

		// Child form is submitted and data is valid, redirect the user to the same page
		if( $childForm->submitted()===true && $childForm->validate()===true )
		{
			$this->_auth->authManager->addItemChild($_GET['name'], $childForm->model->child);
			$this->redirect(array('authItem/update', 'name'=>$_GET['name']));
		}

		// Set the values for the form fields
		$form->model->name = $model->name;
		$form->model->description = $model->description;
		$form->model->type = $model->type;
		$form->model->bizRule = $model->bizRule!=='NULL' ? $model->bizRule : '';
		$form->model->data = $model->data!==NULL ? serialize($model->data) : '';

		// Render the view
		$this->render('update', array(
			'model'=>$model,
			'children'=>$this->_auth->getAuthItemChildren($model, true),
			'parents'=>$this->_auth->getAuthItemParents($model->name),
			'form'=>$form,
			'childForm'=>$childForm,
		));
	}

	/**
	 * Deletes an operation.
	 */
	public function actionDelete()
	{
		// We only allow deletion via POST request
		if( Yii::app()->request->isPostRequest===true )
		{
			$this->_auth->authManager->removeAuthItem($_GET['name']);

			// if AJAX request, we should not redirect the browser
			if( isset($_POST['ajax'])===false )
				$this->redirect(array(isset($_GET['redirect'])===true ? urldecode($_GET['redirect']) : 'main/permissions'));
		}
		else
		{
			throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
		}
	}

	/**
	* Removes a child from an authorization item.
	*/
	public function actionRemoveChild()
	{
		// We only allow deletion via POST request
		if( Yii::app()->request->isPostRequest===true )
		{
			$this->_auth->authManager->removeItemChild($_GET['name'], $_GET['child']);

			// if AJAX request, we should not redirect the browser
			if( isset($_POST['ajax'])===false )
				$this->redirect(array('authItem/update', 'name'=>$_GET['name']));
		}
		else
		{
			throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
		}
	}

	/**
	* Adds a child to an authorization item.
	*/
	public function actionAssign()
	{
		// We only allow deletion via POST request
		if( Yii::app()->request->isPostRequest===true )
		{
			$model = $this->loadModel();

			if( isset($_GET['child'])===true && $model->hasChild($_GET['child'])===false )
				$model->addChild($_GET['child']);

			// if AJAX request, we should not redirect the browser
			if( isset($_POST['ajax'])===false )
				$this->redirect(array('main/permissions'));
		}
		else
		{
			throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
		}
	}

	/**
	* Removes a child from an authorization item.
	*/
	public function actionRevoke()
	{
		// We only allow deletion via POST request
		if( Yii::app()->request->isPostRequest===true )
		{
			$model = $this->loadModel();

			if( isset($_GET['child'])===true && $model->hasChild($_GET['child'])===true )
				$model->removeChild($_GET['child']);

			// if AJAX request, we should not redirect the browser
			if( isset($_POST['ajax'])===false )
				$this->redirect(array('main/permissions'));
		}
		else
		{
			throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
		}
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 */
	public function loadModel()
	{
		if( $this->_model===null )
		{
			if( isset($_GET['name'])===true )
				$this->_model = $this->_auth->authManager->getAuthItem($_GET['name']);

			if( $this->_model===null )
				throw new CHttpException(404,'The requested page does not exist.');
		}

		return $this->_model;
	}
}

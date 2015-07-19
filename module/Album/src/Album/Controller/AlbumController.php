<?php

namespace Album\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Album\Model\Album;         
use Album\Form\AlbumForm;
use ZendSearch\Lucene\Lucene;
use ZendSearch\Lucene\Document;
use ZendSearch\Lucene\Zend_Search_Lucene;
use Album\Form\SearchForm;
 
class AlbumController extends AbstractActionController
{
    protected $albumTable;

	public function indexAction()
	{
	 // grab the paginator from the AlbumTable
	 $paginator = $this->getAlbumTable()->fetchAll(true);
	 // set the current page to what has been passed in query string, or to 1 if none set
	 $paginator->setCurrentPageNumber((int) $this->params()->fromQuery('page', 1));
	 // set the number of items per page to 10
	 $paginator->setItemCountPerPage(10);

	 return new ViewModel(array(
		 'paginator' => $paginator
	 ));
	}
    public function addAction()
     {
         $form = new AlbumForm();
         $form->get('submit')->setValue('Add');

         $request = $this->getRequest();
         if ($request->isPost()) {
             $album = new Album();
             $form->setInputFilter($album->getInputFilter());
             $form->setData($request->getPost());

             if ($form->isValid()) {
                 $album->exchangeArray($form->getData());
                 $this->getAlbumTable()->saveAlbum($album);

                 // Redirect to list of albums
                 return $this->redirect()->toRoute('album');
             }
         }
         return array('form' => $form);
     }


     public function editAction()
     {
         $id = (int) $this->params()->fromRoute('id', 0);
         if (!$id) {
             return $this->redirect()->toRoute('album', array(
                 'action' => 'add'
             ));
         }

         // Get the Album with the specified id.  An exception is thrown
         // if it cannot be found, in which case go to the index page.
         try {
             $album = $this->getAlbumTable()->getAlbum($id);
         }
         catch (\Exception $ex) {
             return $this->redirect()->toRoute('album', array(
                 'action' => 'index'
             ));
         }

         $form  = new AlbumForm();
         $form->bind($album);
         $form->get('submit')->setAttribute('value', 'Edit');

         $request = $this->getRequest();
         if ($request->isPost()) {
             $form->setInputFilter($album->getInputFilter());
             $form->setData($request->getPost());

             if ($form->isValid()) {
                 $this->getAlbumTable()->saveAlbum($album);

                 // Redirect to list of albums
                 return $this->redirect()->toRoute('album');
             }
         }

         return array(
             'id' => $id,
             'form' => $form,
         );
     }

    public function deleteAction()
     {
         $id = (int) $this->params()->fromRoute('id', 0);
         if (!$id) {
             return $this->redirect()->toRoute('album');
         }

         $request = $this->getRequest();
         if ($request->isPost()) {
             $del = $request->getPost('del', 'No');

             if ($del == 'Yes') {
                 $id = (int) $request->getPost('id');
                 $this->getAlbumTable()->deleteAlbum($id);
             }

             // Redirect to list of albums
             return $this->redirect()->toRoute('album');
         }

         return array(
             'id'    => $id,
             'album' => $this->getAlbumTable()->getAlbum($id)
         );
     }
	
	public function searchAction()
	{
	// here I will require the Search form
		$form = new SearchForm();
		$form->get('submit')->setValue('search');
		// here I will create a Lucene's index
		$index = Lucene::create('/indexes');
		$users = $this->getAlbumTable()->fetchAll(true);
		foreach($users as $user)
		{
			$doc = new Document();
			$doc->addField(Document\Field::Text('artist', $user->artist));
			$doc->addField(Document\Field::Text('title',$user->title));
			$index->addDocument($doc);
		}
		$index->commit();
		// here I will search in the saved Lecene's index
		$term = $this->params()->fromQuery('query', false);
		if(!$term):
			$term = "";
			endif;
			$index = Lucene::open('/indexes');
			$results = $index->find($term);
			
		return new ViewModel(array(
			'results' => $results,
			'form' => $form
		));
	}
	public function getIndexLocation()
	{
	// Fetch Configuration from Module Config
	$config = $this->getServiceLocator()->get('config');
	if ($config instanceof Traversable) {
	$config = ArrayUtils::iteratorToArray($config);
	}
	if (!empty($config['module_config']['search_index'])) {
	return $config['module_config']['search_index'];
	} else {
	return FALSE;
	}
	}
	public function generateIndexAction()
	{
	 $searchIndexLocation = $this->getIndexLocation();
	 $index = Lucene\Lucene::create($searchIndexLocation);
	 $userTable = $this->getServiceLocator()->get('Album\Model\AlbumTable');
	$uploadTable = $this->getServiceLocator()->get('Album\Model\AlbumTable');
	 $allUploads = $uploadTable->fetchAll();
	 foreach($allUploads as $fileUpload) {
	 //
	 $uploadOwner = $userTable->getUser($fileUpload->user_id);
	 // create lucene fields
	 $fileUploadId = Document\Field::unIndexed(
	 'upload_id', $fileUpload->id);
	 $label = Document\Field::Text(
	 'label', $fileUpload->label);
	 $owner = Document\Field::Text(
	 'owner', $uploadOwner->name);
	 // create a new document and add all fields
	 $indexDoc = new Lucene\Document();
	 $indexDoc->addField($label);
	 $indexDoc->addField($owner);
	 $indexDoc->addField($fileUploadId);
	 $index->addDocument($indexDoc);
	 }
	 $index->commit();
	}
	public function getAlbumTable()
     {
         if (!$this->albumTable) {
             $sm = $this->getServiceLocator();
             $this->albumTable = $sm->get('Album\Model\AlbumTable');
         }
         return $this->albumTable;
    }
}

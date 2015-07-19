<?php
namespace Album\Form;

 use Zend\Form\Form;

 class SearchForm extends Form
 {
     public function __construct($name = null)
     {
         // we want to ignore the name passed
         parent::__construct('album');

         $this->add(array(
             'name' => 'query',
              'attributes' => array(
			  'type'  => 'text',
			  'id' => 'queryText',
			  'required' => 'required'
			),
             'options' => array(
                 'label' => 'query',
             ),
         ));

         $this->add(array(
             'name' => 'submit',
             'type' => 'submit',
             'attributes' => array(
                 'value' => 'search',
                 'id' => 'submitbutton',
             ),
         ));
     }
 }
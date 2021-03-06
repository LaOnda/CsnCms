<?php

namespace CsnCms\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Zend\Form\Annotation\AnnotationBuilder;

use Zend\Form\Element;

// hydration tests
use Zend\Stdlib\Hydrator;

// for Doctrine annotation
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use DoctrineORMModule\Form\Annotation\AnnotationBuilder as DoctrineAnnotationBuilder;

use CsnCms\Entity\Article;

class TranslationController extends AbstractActionController
{
    // R - retriev
    public function indexAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) return $this->redirect()->toRoute('csn-cms/default', array('controller' => 'article', 'action' => 'index'));

        $entityManager = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
        $dql = "SELECT a, u, l, c, h  FROM CsnCms\Entity\Article a LEFT JOIN a.author u LEFT JOIN a.language l LEFT JOIN a.categories c LEFT JOIN a.children h WHERE a.id = ?1";
        $query = $entityManager->createQuery($dql);
        $query->setMaxResults(30);
        $query->setParameter(1, $id);
        $articles = $query->getResult();

        return new ViewModel(array('articles' => $articles, 'id' => $id));
    }

    // C - create
	public function addAction()
    {
		$id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) return $this->redirect()->toRoute('csn-cms/default', array('controller' => 'article', 'action' => 'index'));
		
        $entityManager = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
        $article = new Article;
		
		try {
            $repository = $entityManager->getRepository('CsnCms\Entity\Article');
            $parent = $repository->findOneBy(array('id' => $id));
            $article->setParent($parent);
        } catch (\Exception $ex) {
            return $this->redirect()->toRoute('csn-cms/default', array('controller' => 'article', 'action' => 'index'));
        }
		
        $form = $this->getForm($article, $entityManager, 'Add');

        $form->bind($article);

        $request = $this->getRequest();
        if ($request->isPost()) {
                $post = $request->getPost();
                $form->setData($post);
                if ($form->isValid()) {
                    $this->prepareData($article);
                    $entityManager->persist($article);
                    $entityManager->flush();

					return $this->redirect()->toRoute('csn-cms/default', array('controller' => 'translation', 'action' => 'index', 'id' => $id), true);
                }
        }

        return new ViewModel(array('form' => $form));
    }

    // U - update
    public function editAction()
    {
        $id = $this->params()->fromRoute('id');
        if (!$id) return $this->redirect()->toRoute('csn-cms/default', array('controller' => 'article', 'action' => 'index'));

        $id = (int) $this->params()->fromRoute('id2', 0);
        if (!$id) return $this->redirect()->toRoute('csn-cms/default', array('controller' => 'translation', 'action' => 'add'), true);

        $entityManager = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');

        try {
            $repository = $entityManager->getRepository('CsnCms\Entity\Article');
            $article = $repository->find($id);
        } catch (\Exception $ex) {
            echo $ex->getMessage(); // this never will be seen fi you don't comment the redirect

            return $this->redirect()->toRoute('csn-cms/default', array('controller' => 'translation', 'action' => 'index'));
        }

        $form = $this->getForm($article, $entityManager, 'Update');

        $form->bind($article);

        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = $request->getPost();
            $form->setData($post);
             if ($form->isValid()) {
                $entityManager->persist($article);
                $entityManager->flush();

                return $this->redirect()->toRoute('csn-cms/default', array('controller' => 'translation', 'action' => 'index'), true);
             }
        }

        return new ViewModel(array('form' => $form, 'id' => $id));
    }

    // D - delete
    public function deleteAction()
    {
        $id = $this->params()->fromRoute('id');
        if (!$id) return $this->redirect()->toRoute('csn-cms/default', array('controller' => 'article', 'action' => 'index'));

        $id = (int) $this->params()->fromRoute('id2', 0);
        if (!$id) return $this->redirect()->toRoute('csn-cms/default', array('controller' => 'translation', 'action' => 'index'), true);

        $entityManager = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');

        try {
            $repository = $entityManager->getRepository('CsnCms\Entity\Article');
            $article = $repository->find($id);
            $entityManager->remove($article);
            $entityManager->flush();
        } catch (\Exception $ex) {
            echo $ex->getMessage(); // this never will be seen fi you don't comment the redirect

            return $this->redirect()->toRoute('csn-cms/default', array('controller' => 'translation', 'action' => 'index'));
        }

        return $this->redirect()->toRoute('csn-cms/default', array('controller' => 'translation', 'action' => 'index'), true);
    }

    public function viewAction()
    {
       // $id = $this->params()->fromRoute('id');
       // if (!$id) return $this->redirect()->toRoute('csn-cms/default', array('controller' => 'translation', 'action' => 'index'));
       //
       // $id = (int) $this->params()->fromRoute('id2', 0);
       // if (!$id) return $this->redirect()->toRoute('csn-cms/default', array('controller' => 'translation', 'action' => 'index'), true);
       //
       // $entityManager = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
       //
       // try {
       //     $repository = $entityManager->getRepository('CsnCms\Entity\Article');
       //     $article = $repository->find($id);
       //     if (!is_object($article)) return $this->redirect()->toRoute('csn-cms/default', array('controller' => 'translation', 'action' => 'index'));
       // } catch (\Exception $ex) {
       //     echo $ex->getMessage(); // this never will be seen fi you don't comment the redirect
       //
       //     return $this->redirect()->toRoute('csn-cms/default', array('controller' => 'translation', 'action' => 'index'));
       // }
       //
       // $sm = $this->getServiceLocator();
       // $auth = $sm->get('Zend\Authentication\AuthenticationService');
       // $config = $sm->get('Config');
       // $acl = $sm->get('acl');
       // // everyone is guest untill it gets logged in
       // $role = \CsnAuthorization\Acl\Acl::DEFAULT_ROLE;
       // if ($auth->hasIdentity()) {
       //     $user = $auth->getIdentity();
       //     $role = $user->getRole()->getName();
       // }
       //
       // $resource = $article->getResource()->getName();
       // $privilege = 'view';
       // if (!$acl->hasResource($resource)) {
       //     throw new \Exception('Resource ' . $resource . ' not defined');
       // }
       //
       // if (!$acl->isAllowed($role, $resource, $privilege)) {
       //     return $this->redirect()->toRoute('home');
       // }
       //
       // return new ViewModel(array('article' => $article));
	   
	    $id = $this->params()->fromRoute('id');
        if (!$id) return $this->redirect()->toRoute('csn-cms/default', array('controller' => 'translation', 'action' => 'index'));
       
        $id = (int) $this->params()->fromRoute('id2', 0);
        if (!$id) return $this->redirect()->toRoute('csn-cms/default', array('controller' => 'translation', 'action' => 'index'), true);

        $entityManager = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');

        try {
            $article = $entityManager->find('CsnCms\Entity\Article', $id);
            if (!is_object($article)) {
                return $this->redirect()->toRoute('csn-cms/default', array('controller' => 'translation', 'action' => 'index'));
            }
        } catch (\Exception $ex) {
            echo $ex->getMessage(); // this will never be seen if you don't comment the redirect

            return $this->redirect()->toRoute('csn-cms/default', array('controller' => 'index', 'action' => 'index'));
        }

        $counterViews = $article->getViewCount();
        $counterViews +=1;
        $article->setViewCount($counterViews);
        $entityManager->persist($article);
        $entityManager->flush();

        //--- Decide whether the user has access to this article ---------------
        $sm = $this->getServiceLocator();
        $auth = $sm->get('Zend\Authentication\AuthenticationService');
        $config = $sm->get('Config');
        $acl = $sm->get('acl');
        // everyone is guest until it gets logged in
        $role = \CsnAuthorization\Acl\Acl::DEFAULT_ROLE;
        if ($auth->hasIdentity()) {
            $user = $auth->getIdentity();
            $role = $user->getRole()->getName();
    }

        $resource = $article->getResource()->getName();
        $privilege = 'view';
        if (!$acl->hasResource($resource)) {
                throw new \Exception('Resource ' . $resource . ' not defined');
        }

        if (!$acl->isAllowed($role, $resource, $privilege)) {
                return $this->redirect()->toRoute('home');
        }
        //END --- Decide whether the user has access to this article -----------

        //--- Get all comments -------------------------------------------------
        $dql = "SELECT c, a FROM CsnCms\Entity\Comment c LEFT JOIN c.article a WHERE a.id = ?1";
        $query = $entityManager->createQuery($dql);
        $query->setMaxResults(30);
        $query->setParameter(1, $id);
        $comments = $query->getResult();
        //END --- Get all comments ---------------------------------------------
		
		$hasUserVoted = $this->hasUserVoted($article);
		
        return new ViewModel(array('article' => $article, 'comments' => $comments, 'hasUserVoted' => $hasUserVoted));
    }

    public function getForm($article, $entityManager, $action)
    {
        $builder = new DoctrineAnnotationBuilder($entityManager);
        $form = $builder->createForm( $article );

        //!!!!!! Start !!!!! Added to make the association tables work with select
        foreach ($form->getElements() as $element) {
            if (method_exists($element, 'getProxy')) {
                $proxy = $element->getProxy();
                if (method_exists($proxy, 'setObjectManager')) {
                    $proxy->setObjectManager($entityManager);
                }
            }
        }

        $form->remove('created');
        $form->remove('parent');
        $form->remove('author');
        $form->setHydrator(new DoctrineHydrator($entityManager,'CsnCms\Entity\Article'));
        $send = new Element('send');
        $send->setValue($action); // submit
        $send->setAttributes(array(
            'type'  => 'submit'
        ));
        $form->add($send);

        return $form;
    }
	
	public function prepareData($article)
    {
        $article->setCreated(new \DateTime());
        $auth = $this->getServiceLocator()->get('Zend\Authentication\AuthenticationService');
        if ($auth->hasIdentity()) {
            $user = $auth->getIdentity();
        }
        $article->setAuthor($user);
		
		$vote = new \CsnCms\Entity\Vote();
		$article->setVote($vote);
    }
	
	public function hasUserVoted($article)
	{
		$entityManager = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
		
		$dql = "SELECT count(v.id) FROM CsnCms\Entity\Vote v LEFT JOIN v.usersVoted u WHERE v.id = ?0 AND u.id =?1";
        $query = $entityManager->createQuery($dql);
		
		$articleId = $article->getVote()->getId();

		$userId = $this->identity();
		$hasUserVoted = 'no';
		
		if($articleId != null && $userId != null)
		{
			$userId = $this->identity()->getId();
			$query->setParameter(0, $articleId);
			$query->setParameter(1, $userId);
			$hasUserVoted = $query->getSingleScalarResult();
		}
		
		return $hasUserVoted;
	}
}

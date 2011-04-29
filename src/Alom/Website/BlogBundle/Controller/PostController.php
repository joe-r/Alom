<?php
/**
 * This file is part of the Alom project.
 *
 * (c) Alexandre Salomé <alexandre.salome@gmail.com>
 *
 * This source file is subject to the GPL license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Alom\Website\BlogBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Alom\Website\BlogBundle\Entity\PostComment;
use Alom\Website\BlogBundle\Form\PostComment as PostCommentForm;

/**
 * Blog post controller
 *
 * @author     Alexandre Salomé <alexandre.salome@gmail.com>
 */
class PostController extends Controller
{
    /**
     * View a blog post
     *
     * @param string $slug Slug of the post to view
     *
     * @return Response
     */
    public function viewAction($slug)
    {
        $em = $this->get('doctrine.orm.default_entity_manager');
        $repository = $em->getRepository('AlomBlogBundle:Post');

        $post = $repository->findOneBySlugWithRelated($slug);
        if (null === $post) {
            throw new NotFoundHttpException("Blog post with slug \"$slug\" not found");
        }

        $form    = $this->get('form.factory')->create(new PostCommentForm());
        $request = $this->get('request');

        if ($request->getMethod() === 'POST') {
            $comment = new PostComment();
            $form->bindRequest($request);
            if ($form->isValid()) {
                $comment = $form->getData();
                $comment->setPost($post);
                $em->persist($comment);
                $em->flush();
                return $this->redirect($this->generateUrl('blog_post_view', array('slug' => $slug)));
            }
        }

        return $this->render('AlomBlogBundle:Post:view.html.twig', array(
            'post'        => $post,
            'commentForm' => $form->createView()
        ));
    }

    /**
     * List all posts.
     *
     * @param int $year Year to display (default = all)
     */
    public function listAction($year = null)
    {
        $posts = $this
            ->get('doctrine.orm.default_entity_manager')
            ->getRepository('AlomBlogBundle:Post')
            ->fetchAllOrderedByDate()
        ;

        if (count($posts) == 0) {
            throw new NotFoundHttpException("No blog post was found");
        }

        return $this->render('AlomBlogBundle:Post:list.html.twig', array(
            'year' => $year,
            'posts' => $posts
        ));
    }
}
<?php

namespace Test\MediaBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Route;

use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\View\View as FOSView;
use Platform\RestBundle\Controller\PlatformRESTController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use \Gedmo\SoftDeleteable\SoftDeleteableListener;
use Test\MediaBundle\Entity\Image;
use Test\MediaBundle\Entity\ImageTag;
use Test\MediaBundle\Entity\Tag;
use Test\MediaBundle\Form\TagType;

/**
 * Tag controller.
 * @RouteResource("Tag")
 */
class TagRESTController extends PlatformRESTController
{
    /**
     * @Route("/tags/{id}")
     *
     * @ApiDoc(
     *  resource = true,
     *  description="Get a Tag entity",
     *  section="Tag api",
     * )
     *
     * @View(serializerEnableMaxDepthChecks=true)
     *
     * @param ParamFetcherInterface $paramFetcher
     * @param int $id
     *
     * @QueryParam(name="include", nullable=true, array=true, description="List of comma separated included fields.
     *     Can be: images or full to return everything.
     *     Example: &include[tag]=image
     * ")
     *
     * @QueryParam(name="noerrors", nullable=true, description="Force server to return incorrect empty response with 20x code in case of 404 response code.")
     * @QueryParam(name="access_token", requirements="", description="token")
     *
     * @return Response|FOSView|array
     *
     */
    public function getAction(ParamFetcherInterface $paramFetcher, $id)
    {
        try {
            if (!$this->checkID($id))
            {
                return $this->respondBadRequest();
            }

            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('TestMediaBundle:Tag')->find($id);

            if ($entity)
            {
                $include = $this->getIncludes($paramFetcher);

                return $this->container->get('tag_json.service')->toJSON($entity, $include);
            }

            return $this->respondNotFoundOrOK('Тег с id ' . $id . ' не найден в базе');
        } catch (\Exception $e) {
            return $this->handleServerError(__FILE__, $e);
        }
    }

    /**
     * @Route("/tags")
     *
     * @ApiDoc(
     *  resource = true,
     *  description="Get all tag entities.",
     *  section="Tag api",
     * )
     *
     * @View(serializerEnableMaxDepthChecks=true)
     *
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return Response|FOSView
     *
     * @QueryParam(name="page", requirements="^[1-9]\d*$", nullable=true, description="Page from which to start listing records. Default: 1.")
     * @QueryParam(name="limit", requirements="^[1-9]\d*$", description="How many records to return. Default: 10. Maximum: 20.")
     * @QueryParam(name="order_by", nullable=true, array=true, description="Order by fields. Must be an array ie. &order_by[name]=ASC&order_by[date]=DESC. Acceptable sort types: ASC, DESC.")
     *
     * @QueryParam(name="include", nullable=true, array=true, description="List of comma separated included fields.
     *     Can be: images or full to return everything.
     *     Example: &include[tag]=image
     * ")
     *
     * @QueryParam(name="noerrors", nullable=true, description="Force server to return incorrect empty response with 20x code in case of 404 response code.")
     * @QueryParam(name="access_token", requirements="", description="token")
     */
    public function getsAction(ParamFetcherInterface $paramFetcher)
    {
        try {
            list($offset, $limit) = $this->getPagination($paramFetcher);

            if ($this->hasErrors())
            {
                return $this->respondBadRequest();
            }

            $order_by = $paramFetcher->get('order_by') ?? [];

            $em = $this->getDoctrine()->getManager();
            $entities = $em->getRepository('TestMediaBundle:Tag')->getBy($order_by, $limit, $offset);

            if (!empty($entities))
            {
                $include = $this->getIncludes($paramFetcher);

                foreach ($entities as &$entity)
                {
                     $entity = $this->container->get('tag_json.service')->toJSON($entity, $include);
                }

                $totalRecords = $em->getRepository('TestMediaBundle:Tag')->getTotalCount();

                $returnCode = (count($entities) / $limit == 1) ? Codes::HTTP_OK : Codes::HTTP_PARTIAL_CONTENT;

                return $this->respondData([
                    'data' => $entities,
                    'paging' => $this->generatePagination($entities, $offset, $limit, $totalRecords),
                ], $returnCode);
            }

            return $this->respondNotFoundOrOK(['paging' => $this->generatePagination([], $offset, $limit, 0)]);
        } catch (\Exception $e) {
            return $this->handleServerError(__FILE__, $e);
        }
    }

    /**
     * @Route("/tags")
     *
     * @ApiDoc(
     *  resource = true,
     *  description="Create a Tag entity",
     *  section="Tag api",
     *  requirements={
     *      {
     *          "name"="name",
     *          "dataType"="string",
     *          "description"="Nosology's name",
     *          "requirement"="_string_"
     *      },
     *      {
     *          "name"="images",
     *          "dataType"="array",
     *          "description"="Linked Image's id",
     *          "requirement"="[_number_ (, _number_)]"
     *      },
     *  }
     * )
     *
     * @QueryParam(name="access_token", requirements="", description="token")
     * @View(statusCode=201, serializerEnableMaxDepthChecks=true)
     *
     * @return Response|FOSView|array
     *
     */
    public function postAction()
    {
        try {
            $entity = new Tag();

            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);

            $with = $this->linkEntities($entity);

            if ($this->handleRequest(new TagType(), $entity))
            {
                $em->flush();

                $include = $this->getIncludes(['tag' => implode(',', $with)]);

                return $this->container->get('tag_json.service')->toJSON($entity, $include);
            }

            return $this->respondUnprocessable();
        } catch (\Exception $e) {
            return $this->handleServerError(__FILE__, $e);
        }
    }

    /**
     * @Route("/tags/{id}")
     *
     * @ApiDoc(
     *  resource = true,
     *  description="Update a Tag entity.",
     *  section="Tag api",
     *  requirements={
     *      {
     *          "name"="name",
     *          "dataType"="string",
     *          "description"="Nosology's name",
     *          "requirement"="_string_"
     *      },
     * }
     * )
     *
     * @QueryParam(name="access_token", requirements="", description="token")
     * @View(serializerEnableMaxDepthChecks=true)
     *
     * @param integer $id
     *
     * @return Response|FOSView|array
     */
    public function putAction($id)
    {
        try {
            if (!$this->checkID($id))
            {
                return $this->respondBadRequest();
            }

            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('TestMediaBundle:Tag')->find($id);

            if (!$entity)
            {
                return $this->respondNotFound('Тег с id ' . $id . ' не найден в базе');
            }

            if ($this->handleRequest(new TagType(), $entity))
            {
                $em->persist($entity);
                $em->flush();

                return $this->container->get('tag_json.service')->toJSON($entity);
            }

            return $this->respondUnprocessable();
        } catch (\Exception $e) {
            return $this->handleServerError(__FILE__, $e);
        }
    }

    /**
     * @Route("/tags/{id}")
     *
     * @ApiDoc(
     *  resource = true,
     *  description="Delete a Tag entity",
     *  section="Tag api",
     * )
     *
     * @QueryParam(name="access_token", requirements="", description="token")
     * @View(statusCode=204)
     *
     * @param integer $id
     *
     * @return Response|FOSView
     */
    public function deleteAction($id)
    {
        try {
            if (!$this->checkID($id))
            {
                return $this->respondBadRequest();
            }

            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('TestMediaBundle:Tag')->find($id);

            if (!$entity)
            {
                return $this->respondNotFound('Тег с id ' . $id . ' не найден в базе');
            }

            $em->remove($entity);
            $em->flush();

            return null;
        } catch (\Exception $e) {
            return $this->handleServerError(__FILE__, $e);
        }
    }

    /**
     * @Route("/tags/{id}/link")
     *
     * @ApiDoc(
     *  resource = true,
     *  description="Links Tag entity to Images. At least one image must be defined",
     *  section="Tag api",
     *  requirements={
     *      {
     *          "name"="images",
     *          "dataType"="array",
     *          "description"="Linked Image IDs",
     *          "requirement"="[_integer_ (,_integer_) ]"
     *      },
     * }
     * )
     *
     * @QueryParam(name="access_token", requirements="", description="token")
     * @View(statusCode=201, serializerEnableMaxDepthChecks=true)
     *
     * @param integer $id
     *
     * @return Response|FOSView|array
     *
     */
    public function postLinkAction($id)
    {
        try {
            if (!$this->checkID($id))
            {
                return $this->respondBadRequest();
            }

            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('TestMediaBundle:Tag')->find($id);

            if (!$entity)
            {
                return $this->respondNotFound('Тег с id ' . $id . ' не найден в базе');
            }

            $with = $this->linkEntities($entity);

            if (empty($with))
            {
                $this->addErrors('Нечего привязать');
            }

            if ($this->hasErrors())
            {
                return $this->respondUnprocessable();
            }

            $em->flush();

            $include = $this->getIncludes(['tag' => implode(',', $with)]);

            return $this->container->get('tag_json.service')->toJSON($entity, $include);
        } catch (\Exception $e) {
            return $this->handleServerError(__FILE__, $e);
        }
    }

    /**
     * @Route("/tags/{id}/unlink")
     *
     * @ApiDoc(
     *  resource = true,
     *  description="Unlinks Tag entity from Images. At least one image must be defined",
     *  section="Tag api",
     *  requirements={
     *      {
     *          "name"="images",
     *          "dataType"="array",
     *          "description"="Unlinked Image IDs",
     *          "requirement"="[_integer_ (,_integer_) ]"
     *      },
     * }
     * )
     *
     * @QueryParam(name="access_token", requirements="", description="token")
     * @View(statusCode=200, serializerEnableMaxDepthChecks=true)
     *
     * @param Request $request
     * @param integer $id
     *
     * @return Response|FOSView|array
     *
     */
    public function postUnlinkAction(Request $request, $id)
    {
        try {
            if (!$this->checkID($id))
            {
                return $this->respondBadRequest();
            }

            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('TestMediaBundle:Tag')->find($id);

            if (!$entity)
            {
                return $this->respondNotFound('Тег с id ' . $id . ' не найден в базе');
            }

            foreach ($em->getEventManager()->getListeners() as $eventName => $listeners)
            {
                foreach ($listeners as $listener)
                {
                    if ($listener instanceof SoftDeleteableListener)
                    {
                        $em->getEventManager()->removeEventListener($eventName, $listener);
                    }
                }
            }

            $em->getFilters()->disable('soft_deleteable');

            $with = [];

            /** Unlink images */
            if (($request->get('images')) && (!empty($request->get('images'))))
            {
                $ids = $this->proceedArrayOfIDs('images');

                $joinEntities = $em->getRepository('TestMediaBundle:ImageTag')->getByTagAndImageIDs($entity, $ids);

                foreach ($joinEntities as $joinEntity)
                {
                    $this->unsetId($ids, $joinEntity->getImage());

                    $entity->removeImage($joinEntity);
                    $em->remove($joinEntity);

                    $with['images'] = 'images';
                }

                foreach ($ids as $id)
                {
                    $this->addErrors('Тег не связан с картинкой с id ' . $id);
                }
            }

            if (empty($with))
            {
                $this->addErrors('Нечего отвязать');
            }

            if ($this->hasErrors())
            {
                return $this->respondUnprocessable();
            }

            $em->flush();

            $include = $this->getIncludes(['tag' => implode(',', $with)]);

            return $this->container->get('tag_json.service')->toJSON($entity, $include);
        } catch (\Exception $e) {
            return $this->handleServerError(__FILE__, $e);
        }
    }

    /**
     * Links Tag to entities, associated with join entity.
     *
     * @param Tag $entity
     * @return array
     */
    private function linkEntities(Tag $entity)
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $em = $this->getDoctrine()->getManager();

        $with = [];

        /** Link images */
        if (($request->get('images')) && (!empty($request->get('images'))))
        {
            $ids = $this->proceedArrayOfIDs('images');

            $targetEntities = $em->getRepository('TestMediaBundle:Image')->getByIDList($ids);

            foreach ($targetEntities as $targetEntity)
            {
                $this->unsetId($ids, $targetEntity);

                $linkEntity = $em->getRepository('TestMediaBundle:ImageTag')->findOneBy(['tag' => $entity, 'image' => $targetEntity]);

                if (!$linkEntity)
                {
                    $linkEntity = new ImageTag();
                    $linkEntity->setTag($entity);
                    $linkEntity->setImage($targetEntity);
                    $em->persist($linkEntity);

                    $entity->addImage($linkEntity);
                }

                $with['images'] = 'images';
            }

            foreach ($ids as $id)
            {
                $this->addErrors('Картинка с id ' . $id . ' не найдена в базе');
            }
        }

        return $with;
    }
}
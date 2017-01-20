<?php

namespace Test\MediaBundle\Controller;

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

/**
 * Image controller.
 * @RouteResource("Image")
 */
class ImageRESTController extends PlatformRESTController
{
    /**
     * @Route("/images/{id}")
     *
     * @ApiDoc(
     *  resource = true,
     *  description="Get a Image entity",
     *  section="Image api",
     * )
     *
     * @View(serializerEnableMaxDepthChecks=true)
     *
     * @param ParamFetcherInterface $paramFetcher
     * @param int $id
     *
     * @QueryParam(name="include", nullable=true, array=true, description="List of comma separated included fields.
     *     Can be: purpose, tags or full to return everything.
     *     Example: &include[image]=purpose,tag
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
            $entity = $em->getRepository('TestMediaBundle:Image')->find($id);

            if ($entity)
            {
                $include = $this->getIncludes($paramFetcher);

                return $this->container->get('media_json.service')->toJSON($entity, $include);
            }

            return $this->respondNotFoundOrOK('Картинка с id ' . $id . ' не найден в базе', true);
        } catch (\Exception $e) {
            return $this->handleServerError(__FILE__, $e);
        }
    }

    /**
     * @Route("/images")
     *
     * @ApiDoc(
     *  resource = true,
     *  description="Get all image entities.",
     *  section="Image api",
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
     *     Can be: purpose, tags or full to return everything.
     *     Example: &include[image]=purpose,tag
     * ")
     *
     * @QueryParam(name="tags", nullable=true, description="Comma separated Tag IDs to filter")
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

            $em = $this->getDoctrine()->getManager();

            $tags = $paramFetcher->get('tags') ?? '';
            $order_by = $paramFetcher->get('order_by') ?? [];

            $tags = explode(',', $tags);
            $errorService = $this->container->get('error.service');

            foreach ($tags as $key => $tag)
            {
                if (!$errorService->checkID($tag))
                {
                    unset($tags[$key]);
                }
            }

            $entities = $em->getRepository('TestMediaBundle:Image')->getBy($order_by, $limit, $offset, $tags);

            if (!empty($entities))
            {
                $include = $this->getIncludes($paramFetcher);

                foreach ($entities as &$entity)
                {
                    $entity = $this->container->get('media_json.service')->toJSON($entity, $include);
                }

                $totalRecords = $em->getRepository('TestMediaBundle:Image')->getTotalCount();

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
     * @Route("/images")
     *
     * @ApiDoc(
     *  resource = true,
     *  description="Uploads an image.",
     *  section="Image api",
     *  requirements={
     *      {
     *          "name"="_any_",
     *          "dataType"="_image_",
     *          "description"="* Image file",
     *          "requirement"="_image_"
     *      },
     *      {
     *          "name"="data",
     *          "dataType"="_json_",
     *          "description"="Image additional data, such as linked tag IDs.",
     *          "requirement"="{'tags':[_integer_ (, _integer_)])"
     *      },
     * }
     * )
     *
     * @QueryParam(name="access_token", requirements="", description="token")
     * @View(statusCode=201, serializerEnableMaxDepthChecks=true)
     *
     * @param Request $request
     *
     * @return Response|FOSView|array
     */
    public function postAction(Request $request)
    {
        try
        {
            $data = $request->get('data');

            if ($data !== null)
            {
                $data = json_decode($data, true);

                if ($data === null)
                {
                    return $this->respondBadRequest('Некорректный формат json в секции data');
                }
            }

            $result = $this->container->get('media.service')->uploadFile(null, ['strictSingleFile' => true]);

            if (isset($result['entity']))
            {
//                $this->container->get('media.service')->setImageData($result['entity'], $data);

                $request->request->replace($data);
                $with = $this->linkEntities($result['entity']);

                $em = $this->getDoctrine()->getManager();
                $em->persist($result['entity']);
                $em->flush();

                $include = $this->getIncludes(['image' => implode(',', $with)]);

                return $this->container->get('media_json.service')->toJSON($result['entity'], $include);
            }

            return $this->respondBadRequest($result['error']);
        } catch (\Exception $e) {
            return $this->container->get('error.service')->handleServerError(__FILE__, $e);
        }
    }

    /**
     * @Route("/images/{id}")
     *
     * @ApiDoc(
     *  resource = true,
     *  description="Changes image file on server.",
     *  section="Image api",
     *  requirements={
     *      {
     *          "name"="_any_",
     *          "dataType"="image",
     *          "description"="Image file (PNG, JPG, GIF)",
     *          "requirement"="_image_"
     *      },
     * }
     * )
     *
     * @View(statusCode=201, serializerEnableMaxDepthChecks=true)
     *
     * @param integer $id
     *
     * @return Response|FOSView
     *
     */
    public function postImageAction($id)
    {
        try
        {
            if (!$this->checkID($id))
            {
                return $this->respondBadRequest();
            }

            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('TestMediaBundle:Image')->find($id);

            if (!$entity)
            {
                return $this->respondNotFound('Изображеня с id ' . $id . ' не существует');
            }

            $oldImage = null;

            if ($entity->getUrl())
            {
                $oldImage = $entity->getUrl();
            }

            $result = $this->container->get('media.service')->uploadFile($entity);

            if (isset($result['entity']))
            {
                $em->flush();

                if ($oldImage)
                {
                    $this->container->get('media.service')->deleteFiles($oldImage);
                }

                $result = array_merge(
                    ['message' => $result['message']],
                    $this->container->get('media_json.service')->toJSON($result['entity'])
                );

                return $this->respondData($result);
            }

            return $this->respondBadRequest($result['error']);
        } catch (\Exception $e) {
            return $this->handleServerError(__FILE__, $e);
        }
    }

    /**
     * @Route("/images/{id}")
     *
     * @ApiDoc(
     *  resource = true,
     *  description="Delete a Image entity",
     *  section="Image api",
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
            $entity = $em->getRepository('TestMediaBundle:Image')->find($id);

            if (!$entity)
            {
                return $this->respondNotFound('Картинка с id ' . $id . ' не найден в базе');
            }

            $em->remove($entity);
            $em->flush();

            return null;
        } catch (\Exception $e) {
            return $this->handleServerError(__FILE__, $e);
        }
    }

    /**
     * @Route("/images/{id}/link")
     *
     * @ApiDoc(
     *  resource = true,
     *  description="Links Image entity to Tags. At least one tag must be defined",
     *  section="Image api",
     *  requirements={
     *      {
     *          "name"="tags",
     *          "dataType"="array",
     *          "description"="Linked Tag IDs",
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
            $entity = $em->getRepository('TestMediaBundle:Image')->find($id);

            if (!$entity)
            {
                return $this->respondNotFound('Картинка с id ' . $id . ' не найден в базе');
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

            $include = $this->getIncludes(['image' => implode(',', $with)]);

            return $this->container->get('media_json.service')->toJSON($entity, $include);
        } catch (\Exception $e) {
            return $this->handleServerError(__FILE__, $e);
        }
    }

    /**
     * @Route("/images/{id}/unlink")
     *
     * @ApiDoc(
     *  resource = true,
     *  description="Unlinks Image entity from Tags. At least one Tag must be defined",
     *  section="Image api",
     *  requirements={
     *      {
     *          "name"="tags",
     *          "dataType"="array",
     *          "description"="Unlinked Tag IDs",
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
            $entity = $em->getRepository('TestMediaBundle:Image')->find($id);

            if (!$entity)
            {
                return $this->respondNotFound('Картинка с id ' . $id . ' не найден в базе');
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

            /** Unlink tags */
            if (($request->get('tags')) && (!empty($request->get('tags'))))
            {
                $ids = $this->proceedArrayOfIDs('tags');

                $joinEntities = $em->getRepository('TestMediaBundle:ImageTag')->getByImageAndTagIDs($entity, $ids);

                foreach ($joinEntities as $joinEntity)
                {
                    $this->unsetId($ids, $joinEntity->getTag());

                    $entity->removeTag($joinEntity);
                    $em->remove($joinEntity);

                    $with['tags'] = 'tags';
                }

                foreach ($ids as $id)
                {
                    $this->addErrors('Картинка не связана с тегом с id ' . $id);
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

            $include = $this->getIncludes(['image' => implode(',', $with)]);

            return $this->container->get('media_json.service')->toJSON($entity, $include);
        } catch (\Exception $e) {
            return $this->handleServerError(__FILE__, $e);
        }
    }

    /**
     * Links Image to entities, associated with join entity.
     *
     * @param Image $entity
     * @return array
     */
    private function linkEntities(Image $entity)
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $em = $this->getDoctrine()->getManager();

        $with = [];

        /** Link tags */
        if (($request->get('tags')) && (!empty($request->get('tags'))))
        {
            $ids = $this->proceedArrayOfIDs('tags');

            $targetEntities = $em->getRepository('TestMediaBundle:Tag')->getByIDList($ids);

            foreach ($targetEntities as $targetEntity)
            {
                $this->unsetId($ids, $targetEntity);

                $linkEntity = $em->getRepository('TestMediaBundle:ImageTag')->findOneBy(['image' => $entity, 'tag' => $targetEntity]);

                if (!$linkEntity)
                {
                    $linkEntity = new ImageTag();
                    $linkEntity->setImage($entity);
                    $linkEntity->setTag($targetEntity);
                    $em->persist($linkEntity);

                    $entity->addTag($linkEntity);
                }

                $with['tags'] = 'tags';
            }

            foreach ($ids as $id)
            {
                $this->addErrors('Тег с id ' . $id . ' не найдена в базе');
            }
        }

        return $with;
    }
}
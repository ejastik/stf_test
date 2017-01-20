<?php

namespace Platform\RestBundle\Controller;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\View\View as FOSView;
use Symfony\Component\HttpFoundation\Response;

class PlatformRESTController extends Controller
{
    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @var array
     */
    protected $details = [];

    /**
     * @var float
     */
    protected $start = 0;

    public function __construct()
    {
        $this->start = microtime(true);
    }

    /**
     * @param string $section
     * @return bool
     */
    protected function checkReadRights($section)
    {
        if ($this->container->get('user_role.service')->getAccessRight($section) == 'N')
        {
            $this->addErrors('Доступ запрещен');
        }

        return (empty($this->errors));
    }

    /**
     * @param string $section
     * @return bool
     */
    protected function checkWriteRights($section)
    {
        if ($this->container->get('user_role.service')->getAccessRight($section) != 'W')
        {
            $this->addErrors('Доступ запрещен');
        }

        return (empty($this->errors));
    }

    /**
     * @param $id
     * @return bool
     */
    protected function checkID($id)
    {
        if (!$this->container->get('error.service')->checkID($id))
        {
            $this->addErrors('ID должен быть положительным числом');
        }

        return (empty($this->errors));
    }

    /**
     * @param string $filename
     * @param \Exception $e
     * @return FOSView
     */
    protected function handleServerError($filename, \Exception $e)
    {
        if ($e instanceof UniqueConstraintViolationException)
        {
            return $this->container->get('error.service')->handleUniqueViolation($e);
        }

        return $this->container->get('error.service')->handleServerError($filename, $e);
    }

    /**
     * @param string|array $errors
     */
    protected function addErrors($errors)
    {
        if (gettype($errors) == 'string')
        {
            $this->errors[] = $errors;
        } elseif (gettype($errors) == 'array') {
            if (
                (isset($errors['errors'])) &&
                (isset($errors['details']))
            )
            {
                $this->errors = array_merge($this->errors, $errors['errors']);
                $this->details = array_merge($this->details, $errors['details']);
            } else {
                $this->errors = array_merge($this->errors, $errors);
            }
        }

        return;
    }

    /**
     * @param string|array $details
     */
    protected function addDetails($details)
    {
        if (gettype($details) == 'string')
        {
            $this->details[] = $details;
        } elseif (gettype($details) == 'array') {
            $this->details = array_merge($this->details, $details);
        }

        return;
    }

    /**
     * @return bool
     */
    protected function hasErrors()
    {
        return (!empty($this->errors));
    }

    /**
     * @param object|null $entity
     * @param array $with
     * @param string|null $fields
     * @param array $options
     * @return array|null
     */
    protected function hydrateEntity($entity = null, $with = [], $fields = null, $options = [])
    {
        return $this->container->get('hydration.service')->hydrateEntity($entity, $with, $fields, $options);
    }

    /**
     * @param ParamFetcherInterface $paramFetcher
     * @return array
     */
    protected function getPagination(ParamFetcherInterface $paramFetcher)
    {
        list($offset, $limit, $errors) = $this->container->get('pagination.service')->getPagination($paramFetcher->get('page'), $paramFetcher->get('limit'));

        $this->addErrors($errors);

        return [$offset, $limit];
    }

    /**
     * @param $formType
     * @param $entity
     * @param null $data
     * @return boolean
     */
    protected function handleRequest($formType, &$entity, $data = null)
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();

        if ($request->get('translations'))
        {
            $this->addErrors($this->get('translation.service')->setEntityTranslations($entity, $request->get('translations')));
        }

        if ($request->getMethod() == 'PUT')
        {
            $request->setMethod('PATCH');
        }

        $form = $this->container->get('form.factory')->createNamed(null, $formType, $entity, ['method' => $request->getMethod()]);

        if ($data === null)
        {
            $data = array_intersect_key($request->request->all(), $form->all());
            $request->request->replace($data);
            $form->handleRequest($request);
        } else {
            $data = array_intersect_key($data, $form->all());
            $form->submit($data, false);
        }

        $this->addErrors($this->container->get('error.service')->processFormErrors($form));

        if ($request->getMethod() == 'POST')
        {
            $this->addErrors($this->container->get('error.service')->processNullFields($entity));
        }

        return (empty($this->errors));
    }

    /**
     * @param string $section
     * @return array
     */
    protected function proceedArrayOfIDs($section)
    {
        $ids = $this->container->get('request_stack')->getCurrentRequest()->get($section);
        $this->addErrors($this->container->get('error.service')->checkArrayOfIDs($ids, $section));

        return $ids;
    }

    /**
     * @param array $ids
     * @param $targetEntity
     */
    protected function unsetId(&$ids, $targetEntity)
    {
        $this->addErrors($this->container->get('error.service')->unsetId($ids, $targetEntity));
        return;
    }

    /**
     * @param array $array
     * @param integer $from
     * @param integer $to
     * @return array
     */
    protected function moveElement(&$array, $from, $to)
    {
        array_splice($array, $to, 0, array_splice($array, $from, 1));
        return $array;
    }

    /**
     * @param array $entities
     * @param integer $offset
     * @param integer $limit
     * @param integer $totalRecords
     * @return array
     */
    protected function generatePagination($entities, $offset, $limit, $totalRecords)
    {
        return $this->container->get('pagination.service')->generatePagination($entities, $offset, $limit, $totalRecords);
    }

    /**
     * @param array|string|null $errors
     * @param bool $forceAppend
     * @return array
     */
    private function getErrors($errors, $forceAppend = false)
    {
        if (
            (
                (gettype($errors) == 'array') &&
                (!empty($errors))
            ) ||
            (
                (gettype($errors) == 'string') &&
                ($errors !== '')
            )
        )
        {
            if ($forceAppend)
            {
                $this->addErrors($errors);

                return $this->errors;
            } else {
                return (gettype($errors) == 'array') ? $errors : [$errors];
            }
        } else {
            if ($forceAppend)
            {
                return [];
            } elseif (!empty($this->errors)) {
                return $this->errors;
            } else {
                return ['Неопознанная ошибка'];
            }
        }
    }

    /**
     * @param array|string|null $errors
     * @param bool $forceAppend
     * @return FOSView
     */
    protected function respondForbidden($errors = null, $forceAppend = false)
    {
        return FOSView::create([
            'errors' => $this->getErrors($errors, $forceAppend),
            'rights' => $this->container->get('user_role.service')->getUserRights(),
        ], Codes::HTTP_FORBIDDEN);
    }

    /**
     * @param array|string|null $errors
     * @param bool $forceAppend
     * @return FOSView
     */
    protected function respondBadRequest($errors = null, $forceAppend = false)
    {
        return FOSView::create([
            'errors' => $this->getErrors($errors, $forceAppend),
            'details' => ((!empty($this->details) ? $this->details : null)),
        ], Codes::HTTP_BAD_REQUEST);
    }

    /**
     * @param array|string|null $errors
     * @param bool $forceAppend
     * @return FOSView
     */
    protected function respondUnprocessable($errors = null, $forceAppend = false)
    {
        return FOSView::create([
            'errors' => $this->getErrors($errors, $forceAppend),
            'details' => ((!empty($this->details) ? $this->details : null)),
        ], Codes::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @param array|string|null $errors
     * @param bool $forceAppend
     * @return FOSView
     */
    protected function respondNotFound($errors = null, $forceAppend = false)
    {
        return FOSView::create([
            'errors' => $this->getErrors($errors, $forceAppend),
            'details' => ((!empty($this->details) ? $this->details : null)),
        ], Codes::HTTP_NOT_FOUND);
    }

    /**
     * @param array|string|null $errors
     * @param bool $forceErrors
     * @return FOSView
     */
    protected function respondNotFoundOrOK($errors = null, $forceErrors = false)
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();

        if ($request->query->get('noerrors') !== null)
        {
            if ($forceErrors)
            {
                return FOSView::create([], Codes::HTTP_OK);
            } else {
                return FOSView::create($errors ?? [], Codes::HTTP_OK);
            }
        } else {
            if (
                ($errors === null) ||
                (!$forceErrors)
            )
            {
                $errors = 'Данных по вашему запросу не найдено';
            }

            return FOSView::create([
                'errors' => (gettype($errors) == 'array') ? $errors : [$errors],
                'details' => ((!empty($this->details) ? $this->details : null)),
            ], Codes::HTTP_NOT_FOUND);
        }
    }

    /**
     * @param array|null $data
     * @param null $code
     * @param null|boolean $tms
     * @return FOSView
     */
    protected function respondData($data = null, $code = null, $tms = null)
    {
        if ($code === null)
        {
            switch ($this->container->get('request_stack')->getCurrentRequest()->getMethod())
            {
                case 'GET':
                    $code = Codes::HTTP_OK;
                    break;
                case 'POST':
                    $code = Codes::HTTP_CREATED;
                    break;
                case 'PUT':
                    $code = Codes::HTTP_OK;
                    break;
                case 'PATCH':
                    $code = Codes::HTTP_OK;
                    break;
                case 'DELETE':
                    $code = Codes::HTTP_NO_CONTENT;
                    break;
                default:
                    $code = Codes::HTTP_OK;
                    break;
            }
        }

        $includeTMS = $tms ?? (($this->container->hasParameter('include_tms')) ? $this->container->getParameter('include_tms') : false);

        if ($includeTMS)
        {
            $data['tms'] = ceil((microtime(true) - $this->start) * 1000);
        }

        return FOSView::create($data, $code);
    }

    /**
     * @param array $data
     * @param integer $code
     * @return Response
     */
    public function responseFor1C($data, $code)
    {
        return new Response(
            mb_convert_encoding(json_encode($data, JSON_UNESCAPED_UNICODE), 'windows-1251', 'utf-8'),
            $code,
            ['Content-Type' => 'application/json;charset=windows-1251']
        );

    }

    /**
     * @param ParamFetcherInterface|array $includes
     * @return array
     */
    protected function getIncludes($includes)
    {
        if ($includes instanceof ParamFetcherInterface)
        {
            $includes = $includes->get('include', false) ?? [];
        }

        return $this->container->get('parameter.service')->processIncludes($includes);
    }
}
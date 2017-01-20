<?php

namespace Platform\RestBundle\Services;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\Form;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\View\View as FOSView;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Platform\UserBundle\Entity\User;

class ErrorService
{
    protected $container;
    protected $em;

    /**
     * __construct
     *
     * @param ContainerInterface $container
     * @param EntityManager $entityManager
     */
    public function __construct(ContainerInterface $container, EntityManager $entityManager)
    {
        $this->container = $container;
        $this->em = $entityManager;
    }

    /**
     * @param string $filename
     * @param \Exception $exception
     * @return FOSView
     */
    public function handleServerError($filename, \Exception $exception)
    {
        if ($exception instanceof AccessDeniedHttpException)
        {
            return $this->handleNoPermissionError($exception);
        }

        $environment = $this->container->get('kernel')->getEnvironment();

        $request = $this->container->get('request_stack')->getCurrentRequest();

        if ($environment == 'prod')
        {
            if (!($exception instanceof AuthenticationCredentialsNotFoundException))
            {
                try {
                    $userToken = $this->container->get('security.token_storage')->getToken();
//                    $userToken = $this->container->get('security.context')->getToken();
                    $user = ($userToken) ? $userToken->getUser() : null;
                    $username = ($user instanceof User) ? $user->getUsername() : 'Невідомий';
                } catch (\Exception $e) {
                    $username = 'Невідомий';
                }

                try {
                    $slackConfig = ($this->container->hasParameter('slack')) ? $this->container->getParameter('slack') : [];

                    if ((isset($slackConfig['key'])) && (isset($slackConfig['channel'])))
                    {
                        $data = [
                            'channel' => '#' . $slackConfig['channel'],
                            'username' => $slackConfig['name'] ?? 'Buggy Ghost',
                            'text' => date("d.m.Y H:i:s") . PHP_EOL .
                                '`' . $username . '` з адреси `' . $request->getClientIp() . '`' . PHP_EOL .
                                'Своїми діями викликав помилку `' . $exception->getCode() . '`: `' . str_replace('`', '"', $exception->getMessage()) . '`' . PHP_EOL .
                                'У файлі `' . $exception->getFile() . '` у рядку `' . $exception->getLine() . '`' . PHP_EOL .
                                'Залоговану з файлу `' . $filename . '`' . PHP_EOL .
                                'Запросивши URL `' . $request->getMethod() . '` `' . $request->getRequestUri() . '`' . PHP_EOL .
                                'З параметрами `' . json_encode($request->request->all()) . '`',
                            'icon_emoji' => $slackConfig['icon'] ?? ':ghost:',
                        ];

                        $ch = curl_init('https://hooks.slack.com/services/' . $slackConfig['key']);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, ['payload' => json_encode($data)]);
                        curl_setopt($ch, CURLOPT_HEADER, 0);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                        $result = curl_exec($ch);
                        curl_close($ch);

                        if ($result == 'ok')
                        {
//                            return FOSView::create(['errors' => ['Your request can not be processed now']], Codes::HTTP_BAD_REQUEST);

                            return $this->respondError($exception);
                        }
                    }
                } catch (\Exception $e) {}

                try {
                    file_put_contents($this->container->get('kernel')->getRootDir() . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'errors.log',

                        'Error ' . $exception->getCode() . ': ' . $exception->getMessage() . PHP_EOL .
                        'Logged from: ' . $filename . PHP_EOL .
                        'In file: ' . $exception->getFile() . ', line: ' . $exception->getLine() .PHP_EOL .
                        'Called by: "' . $username . '" from IP address: ' . $this->container->get('request')->getClientIp() . PHP_EOL .
                        date("Y-m-d H:i:s") . PHP_EOL .
                        'By requesting URL: ' . $request->getMethod() . ' ' . $request->getRequestUri() . PHP_EOL .
                        'With parameters: ' . json_encode($request->request->all()) . PHP_EOL . PHP_EOL,

                        FILE_APPEND | LOCK_EX);
                } catch (\Exception $e) {}
            } else {
                return $this->handleUnauthorizedError();
            }

            return $this->respondError($exception);
        } else {
            if (!($exception instanceof AuthenticationCredentialsNotFoundException))
            {
                if (
                    ($exception->getCode() == 0) &&
                    (strpos($exception->getMessage(), 'No route found for ') !== false)
                )
                {
                    $returnCode = Codes::HTTP_NOT_FOUND;
                } else {
                    $returnCode = Codes::HTTP_INTERNAL_SERVER_ERROR;
                }

                return FOSView::create([
                    'errors' => [
                        'Internal server error ' . $exception->getCode() . ': ' . str_replace(['"', '\''], ['`', '`'], $exception->getMessage()),
                        'In file: ' . $exception->getFile(),
                        'In line: ' . $exception->getLine(),
                        'Logged from ' . $filename,
                        'By requesting URL: ' . $request->getMethod() . ' ' . $request->getRequestUri(),
                        'With parameters: ' . str_replace(['"', '\''], ['`', '`'], json_encode($request->request->all()))
                    ],
                ], /*$exception->getCode()*/$returnCode);
            } else {
                return $this->handleUnauthorizedError();
            }
        }
    }

    /**
     * @param \Exception $exception
     * @return FOSView
     */
    private function respondError(\Exception $exception)
    {
        if (
            ($exception->getCode() == 0) &&
            (strpos($exception->getMessage(), 'No route found for ') !== false)
        )
        {
            return FOSView::create($exception->getMessage(), Codes::HTTP_NOT_FOUND);
        }

        switch ($exception->getCode())
        {
            case 404:
                return FOSView::create(['errors' => [$exception->getMessage()]], Codes::HTTP_NOT_FOUND);
                break;
            case 500:
                return FOSView::create(['errors' => ['Ваш запрос сейчас не может быть обработан']], Codes::HTTP_INTERNAL_SERVER_ERROR);
                break;
            default:
                return FOSView::create(['errors' => ['Ваш запрос не может быть обработан']], Codes::HTTP_BAD_REQUEST);
                break;
        }
    }

    /**
     * @return FOSView
     */
    public function handleUnauthorizedError()
    {
        return FOSView::create(['errors' => ['Error code ' . Codes::HTTP_UNAUTHORIZED . ': you are not authenticated']], Codes::HTTP_UNAUTHORIZED);
    }

    /**
     * @param \Exception $e
     * @return FOSView
     */
    public function handleNoPermissionError(\Exception $e)
    {
        return FOSView::create(['errors' => ['Error code ' . Codes::HTTP_FORBIDDEN . ': ' . $e->getMessage()]], Codes::HTTP_FORBIDDEN);
    }

    /**
     * @param UniqueConstraintViolationException $e
     * @return FOSView
     */
    public function handleUniqueViolation(UniqueConstraintViolationException $e)
    {
        $error = explode(PHP_EOL, $e->getMessage());
        $error = array_pop($error);
        $error = str_replace('DETAIL:', '', $error);
        $error = trim($error);

        $details = [];

        if (strpos($error, 'Key (') === 0)
        {
            $startPos = strpos($error, ')=(');

            if ($startPos !== false)
            {
                $details[substr($error, 5, $startPos - 5)][] = 'Не уникальное значение поля';
            }

            $count = 1;
            $error = str_replace('Key', 'Запись с полем', $error, $count);
        }

        if (strpos($error, ') already exists.') === strlen($error) - 17)
        {
            $error = substr_replace($error, 'уже существует', strlen($error) - 15, 15);
        }

        return FOSView::create(['errors' => [$error], 'details' => $details], Codes::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @param Form $form
     * @return array
     */
    public function processFormErrors($form)
    {
        $errors = [];
        $details = [];

        if (!$form->isSubmitted())
        {
//            $errors[] = 'Request does not contain any of required data';
            $errors[] = 'Запрос не содержит никаких необходимых данных';
        }

        if (!$form->isValid())
        {
            foreach ($form as $fieldName => $formField)
            {
                if (!$formField->isValid())
                {
                    foreach ($formField->getErrors() as $error)
                    {
                        $errors[] = $this->mb_ucfirst($fieldName) . ' error: ' . $error->getMessage();
                        $details[$fieldName] = $error->getMessage();
                    }
                }
            }
        }

        return ['errors' => $errors, 'details' => $details];
    }

    /**
     * @param $id
     * @return bool
     */
    public function checkID($id)
    {
        return (($id) && (is_numeric($id)) && (preg_match('/^[1-9]\d*$/', $id)));
    }

    /**
     * @param $ids
     * @param string $section
     * @return array
     */
    public function checkArrayOfIDs(&$ids, $section)
    {
        $errors = [];

        if (gettype($ids) != 'array')
        {
            $ids = [];
//            $errors[] = 'Wrong format of request in ' . $section . ' section: must be an array of IDs';
            $errors[] = 'Некорректный формат запроса в секции ' . $section . ': должен быть массив ID';
        } else {
            foreach ($ids as $key => $id)
            {
                if (!$this->checkID($id))
                {
//                    $errors[] = 'Wrong format of request in ' . $section . ' section: id value `' . $id . '` is incorrect (must be a positive integer)';
                    $errors[] = 'Некорректный формат запроса в секции ' . $section . ': значение id `' . $id . '` некорректно (должно быть положительное число)';
                    unset($ids[$key]);
                }
            }
        }

        return $errors;
    }

    /**
     * @param array $ids
     * @param $targetEntity
     * @return array
     */
    public function unsetId(&$ids, $targetEntity)
    {
        if (
            (!$targetEntity) ||
            (gettype($targetEntity) != 'object') ||
            (gettype($ids) != 'array') ||
            (empty($ids))
        )
        {
            return [];
        }

        $errors = [];
        $keys = array_keys($ids, $targetEntity->getId());

        if (count($keys) > 1)
        {
            $targetEntityMetadata = $this->em->getClassMetadata(get_class($targetEntity));
            $targetEntityName = array_pop(explode('\\', $targetEntityMetadata->getName()));
            $targetEntityName = mb_strtolower($targetEntityName);

//            $errors[] = 'Link to ' . $targetEntityName . ' with id ' . $targetEntity->getId() . ' specified multiple times';
            $errors[] = 'Связь с ' . $targetEntityName . ' с id ' . $targetEntity->getId() . ' указана несколько раз';
        }

        foreach ($keys as $key)
        {
            unset($ids[$key]);
        }

        return $errors;
    }

    /**
     * @param $entity
     * @return array
     */
    public function checkComment($entity)
    {
        if ((!$entity) || (gettype($entity) != 'object') || (!method_exists($entity, 'getText')))
        {
            return [];
        }

        $errors = [];
        $comment = ($this->container->hasParameter('comment')) ? $this->container->getParameter('comment') : [];
        $commentLength = strlen($entity->getText());
        $minLength = $comment['min_length'] ?? null;
        $maxLength = $comment['max_length'] ?? null;

        if (
            (
                ($minLength) &&
                ($commentLength < $minLength)
            ) ||
            (
                ($maxLength) &&
                ($commentLength > $maxLength)
            )
        )
        {
//            $errors[] = 'Comment must be ' . $minLength . '..' . $maxLength . ' characters length';
            $errors[] = 'Комментарий должен быть от ' . $minLength . ' до ' . $maxLength . ' символов в длинну';
        }

        return $errors;
    }

    /**
     * @param $entity
     * @return array
     */
    public function processNullFields(&$entity)
    {
        if ((!$entity) || (gettype($entity) != 'object'))
        {
            return [];
        }

        $errors = [];
        $details = [];
        $entityMetadata = $this->em->getClassMetadata(get_class($entity));
        $hydrationService = $this->container->get('hydration.service');

        foreach ($entityMetadata->getFieldNames() as $field)
        {
            if (!in_array($field, $entityMetadata->getIdentifier()))
            {
                $getField = $hydrationService->getGetter($entity, $field);
                $fieldMapping = $entityMetadata->getFieldMapping($field);

                if (
                    ($getField) &&
                    ($entity->$getField() === null) &&
                    (!$fieldMapping['nullable'])
                )
                {
                    $setField = $hydrationService->getSetter($entity, $field);

                    if (
                        ($setField) &&
                        (isset($fieldMapping['options'])) &&
                        (isset($fieldMapping['options']['default']))
                    )
                    {
                        $entity->$setField($fieldMapping['options']['default']);
                    } else {
//                        $errors[] = $this->mb_ucfirst($field) . ' error: value must be defined';
                        $errors[] = 'Ошибка в поле ' . $this->mb_ucfirst($field) . ': значение должно быть определено';
                        $details[$field][] = 'Должно быть указано';
                    }
                }
            }
        }

        return ['errors' => $errors, 'details' => $details];
    }

    /**
     * mb_ucfirst
     *
     * @param string $str
     * @return string
     */
    private function mb_ucfirst($str)
    {
        return mb_strtoupper(mb_substr($str, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($str, 1, mb_strlen($str) - 1, 'UTF-8');
    }

}
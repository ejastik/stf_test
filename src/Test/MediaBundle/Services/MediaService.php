<?php

namespace Test\MediaBundle\Services;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Test\MediaBundle\Entity\Image;

class MediaService
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
     * @param Image $image
     * @return string
     */
    public function generateImageURL(Image $image)
    {
        $result = [];

        $url = $image->getUrl();

        if ($url)
        {
            $imageConfigs = ($this->container->hasParameter('image_dirs')) ? $this->container->getParameter('image_dirs') : [];

            $dirs = [];
            $defaultDir = '';

            foreach ($imageConfigs as $key => $config)
            {
                if (
                    (
                        (!empty($config['only_for'] ?? [])) &&
                        (!in_array($image->getImageType(), $config['only_for']))
                    ) ||
                    (in_array($image->getImageType(), $config['not_for'] ?? []))
                )
                {
                    continue;
                }

                if (isset($config['dir']))
                {
                    $dirs[$key] = $config['dir'];

                    if ((isset($config['default'])) && ($config['default']))
                    {
                        $defaultDir = $config['dir'];
                    }
                }
            }

            foreach ($dirs as $key => $dir)
            {
                $result['url_' . $key] = $dir . DIRECTORY_SEPARATOR . $url;
            }

            if ($defaultDir)
            {
                $result['url'] = $defaultDir . DIRECTORY_SEPARATOR . $url;
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function generateNoimageURL()
    {
        $result = [];

        $imageConfigs = $this->container->getParameter('image_dirs');

        $dirs = [];
        $noimages = [];
        $defaultDir = '';
        $defaultNoimage = '';

        foreach ($imageConfigs as $key => $config)
        {
            if ((isset($config['dir'])) && (isset($config['noimage'])))
            {
                $dirs[$key] = $config['dir'];
                $noimages[$key] = $config['noimage'];

                if ((isset($config['default'])) && ($config['default']))
                {
                    $defaultDir = $config['dir'];
                    $defaultNoimage = $config['noimage'];
                }
            }
        }

        foreach ($dirs as $key => $dir)
        {
            $result['url_' . $key] = $dir . DIRECTORY_SEPARATOR . $noimages[$key];
        }

        $result['url'] = $defaultDir . DIRECTORY_SEPARATOR . $defaultNoimage;

        return $result;
    }

    /**
     * @param $file
     * @return null|string
     */
    public function checkFileForErrors($file)
    {
        if ($file['error'])
        {
            switch ($file['error'])
            {
                case UPLOAD_ERR_INI_SIZE:
//                    $message = "The uploaded file exceeds global server max filesize"; //upload_max_filesize directive in php.ini
                    $message = "Размер загруженного файла превышает серверный лимит"; //upload_max_filesize directive in php.ini
                    break;
                case UPLOAD_ERR_FORM_SIZE:
//                    $message = "The uploaded file exceeds max filesize"; //MAX_FILE_SIZE directive that was specified in the HTML form
                    $message = "Размер загруженного файла превышает допустимый лимит"; //MAX_FILE_SIZE directive that was specified in the HTML form
                    break;
                case UPLOAD_ERR_PARTIAL:
//                    $message = "The uploaded file was only partially uploaded";
                    $message = "Файл был загружен только частично";
                    break;
                case UPLOAD_ERR_NO_FILE:
//                    $message = "No file was uploaded";
                    $message = "Файл не был загружен";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
//                    $message = "Missing a temporary folder";
                    $message = "Директория временных файлов недоступна";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
//                    $message = "Failed to write file to disk";
                    $message = "Не удалось сохранить файл";
                    break;
                case UPLOAD_ERR_EXTENSION:
//                    $message = "File upload stopped by some php extension";
                    $message = "Загрузка файла была прервана одним из дополнений php";
                    break;
                default:
//                    $message = "Error uploading file";
                    $message = "Ошибка загрузка файла";
                    break;
            }

            return $message;
        }

        return null;
    }

    /**
     * @param string|null $imageType
     *
     * @return array
     */
    public function uploadFiles($imageType = null)
    {
        $entities = [];

        foreach ($_FILES as $file)
        {
            $entities[] = $this->uploadFile(null, ['strictSingleFile' => false, 'imageType' => $imageType]);
        }

        $this->em->flush();

        return $entities;
    }

    /**
     * @param null $entity
     * @param array $options
     * @return array
     */
    public function uploadFile($entity = null, $options = [])
    {
        if (empty($_FILES))
        {
//            return ['error' => 'No images uploaded'];
            return ['error' => 'Ни одного файла не было загружено'];
        }

        if (($options['strictSingleFile'] ?? true) && (count($_FILES) > 1))
        {
//            return ['error' => 'Only 1 image expected'];
            return ['error' => 'Ожидается только 1 файл'];
        }

        $file = array_pop($_FILES);

        $error = $this->checkFileForErrors($file);

        if ($error)
        {
            return ['error' => $error];
        }

        return $this->makeImageFromFile($file, $entity, $options);
    }

    /**
     * @param array $file
     * @param null $entity
     * @param array $options
     * @return array
     */
    public function makeImageFromFile($file, $entity = null, $options = [])
    {
//        $isMainImage = $options['isMainImage'] ?? null;
        $imageType = $options['imageType'] ?? ((($entity) && ($entity instanceof Image)) ? $entity->getImageType() : null);
        $keepFileName = $options['keepFileName'] ?? null;

        if ((!isset($file['tmp_name'])) || (!isset($file['name'])))
        {
//            return ['error' => 'Incorrect file info array format'];
            return ['error' => 'Некорректный формат информации о файле'];
        }

        $originalsFilesystem = $this->container->get('knp_gaufrette.filesystem_map')->get('originals');

        $filesystems = [];
        $imageConfigs = ($this->container->hasParameter('image_dirs')) ? $this->container->getParameter('image_dirs') : [];

        $maxWidth = 0;
        $maxHeight = 0;

        foreach ($imageConfigs as $key => $config)
        {
            if (
                (isset($config['dir'])) &&
                (isset($config['size'])) &&
                (gettype($config['size'] == 'array')) &&
                (isset($config['size']['width'])) &&
                (isset($config['size']['height']))
            )
            {
                $filesystems[$key] = [
                    'filesystem' => $this->container->get('knp_gaufrette.filesystem_map')->get($key),
                    'dir' => $config['dir'],
                    'width' => $config['size']['width'],
                    'height' => $config['size']['height'],
                    'png_compression_level' => $config['png_compression_level'] ?? $this->container->getParameter('png_compression_level'),
                    'jpeg_quality' => $config['jpeg_quality'] ?? $this->container->getParameter('jpeg_quality'),
                    'crop' => $config['crop'] ?? false,
                    'enlarge' => $config['enlarge'] ?? false,
                    'only_for' => $config['only_for'] ?? [],
                    'not_for' => $config['not_for'] ?? [],
                ];

                if ($maxWidth < $config['size']['width'])
                {
                    $maxWidth = $config['size']['width'];
                }

                if ($maxHeight < $config['size']['height'])
                {
                    $maxHeight = $config['size']['height'];
                }
            }
        }

        if (!in_array(mime_content_type($file['tmp_name']), $this->container->getParameter('allowed_types')))
        {
//            return ['error' => 'Media type ' . mime_content_type($file['tmp_name']) . ' is not allowed'];
            return ['error' => 'Тип медиа ' . mime_content_type($file['tmp_name']) . ' не разрешен'];
        }

        $imageInfo = getimagesize($file['tmp_name']);
        $retries = ($this->container->hasParameter('file_save_retries')) ? $this->container->getParameter('file_save_retries') : 100;
        $nestingLevel = ($this->container->hasParameter('file_nesting_level')) ? $this->container->getParameter('file_nesting_level') : 4;

        $fileExtension = $this->getExtension($file);

        if (!$fileExtension)
        {
//            return ['error' => 'Unable to determine file extension'];
            return ['error' => 'Невозможно определить разширение файла'];
        }

        $count = 0;

        if (!$keepFileName)
        {
            do
            {
                $filename = $this->generateRandomFileName() . '.' . $fileExtension;
                $count++;
                $dir = '';

                for ($i = 0; $i < $nestingLevel; $i++)
                {
                    $dir .= sprintf('%02d' . DIRECTORY_SEPARATOR, mt_rand(0, 99));
                }
            } while (($count < $retries) && ($originalsFilesystem->has($dir . $filename)));
        } else {
            $dir = '';
            $filename = $file['name'];
        }

        if ($count < $retries)
        {
            $imagine = new Imagine();

            $config = ($this->container->hasParameter('original_images')) ? $this->container->getParameter('original_images') : [];

            if (
                (isset($config['size'])) &&
                (isset($config['size']['width'])) &&
                (isset($config['size']['height'])) &&
                (
                    (isset($imageInfo[0])) &&
                    ($imageInfo[0] > $config['size']['width'])
                ) ||
                (
                    (isset($imageInfo[1])) &&
                    ($imageInfo[1] > $config['size']['height'])
                )
            )
            {
                $png_compression_level = $config['png_compression_level'] ?? $this->container->getParameter('png_compression_level');
                $jpeg_quality = $config['jpeg_quality'] ?? $this->container->getParameter('jpeg_quality');

                $imageData = $imagine->open($file['tmp_name'])
                    ->thumbnail(new Box($config['size']['width'], $config['size']['height']), ImageInterface::THUMBNAIL_INSET)
                    ->get($fileExtension, ['png_compression_level' => $png_compression_level, 'jpeg_quality' => $jpeg_quality]);
            } else {
                $imageData = file_get_contents($file['tmp_name']);
            }

            if (($keepFileName) && ($originalsFilesystem->has($dir . $filename)))
            {
                $originalsFilesystem->delete($dir . $filename);
            }

            $originalsFilesystem->write($dir . $filename, $imageData);

            $watermark = ($this->container->hasParameter('watermark')) ? $this->container->getParameter('watermark') : [];

            foreach ($filesystems as $filesystem)
            {
                if (
                    (
                        (!empty($filesystem['only_for'])) &&
                        (!in_array($imageType, $filesystem['only_for']))
                    ) ||
                    (in_array($imageType, $filesystem['not_for']))
                )
                {
                    continue;
                }

                $sizing = ($filesystem['crop']) ? ImageInterface::THUMBNAIL_OUTBOUND : ImageInterface::THUMBNAIL_INSET;

                $image = $imagine->open($file['tmp_name']);

                if (
                    ($filesystem['enlarge']) &&
                    (isset($imageInfo[0])) &&
                    (isset($imageInfo[1])) &&
                    (
                        ($imageInfo[0] < $filesystem['width']) ||
                        ($imageInfo[1] < $filesystem['height'])
                    )
                )
                {
                    $scaleX = $filesystem['width'] / $imageInfo[0];
                    $scaleY = $filesystem['height'] / $imageInfo[1];

                    $scale = max($scaleX, $scaleY);
                    $image = $image->resize(new Box($imageInfo[0] * $scale, $imageInfo[1] * $scale));
                }

                $image = $image->thumbnail(new Box($filesystem['width'], $filesystem['height']), $sizing);

//                $this->addWatermark($image, $watermark);

                if (($keepFileName) && ($filesystem['filesystem']->has($dir . $filename)))
                {
                    $filesystem['filesystem']->delete($dir . $filename);
                }

                $filesystem['filesystem']->write(
                    $dir . $filename,
                    $image->get($fileExtension, ['png_compression_level' => $filesystem['png_compression_level'], 'jpeg_quality' => $filesystem['jpeg_quality']])
                );
            }

            $latitude = null;
            $longitude = null;
            $date = null;

            if ((!$entity) || !($entity instanceof Image))
            {
                $entity = new Image();
            }

            $entity->setUrl($dir . $filename);
            $entity->setImageType($imageType);

            $this->em->persist($entity);

            if (
                (
                    (isset($imageInfo[0])) &&
                    ($imageInfo[0] < $maxWidth)
                ) ||
                (
                    (isset($imageInfo[1])) &&
                    ($imageInfo[1] < $maxHeight)
                )
            )
            {
//                $message = 'Uploaded image size is too small, quality of generated images may be low';
                $message = 'Размер загруженного изображения очень мал, сгенерированные картинки могут иметь низкое качество';
            } else {
                $message = null;
            }

            return ['entity' => $entity, 'message' => $message];
        }

//        return ['error' => 'Unable to save ' . $file['name'] . ' file'];
        return ['error' => 'Невозможно сохранить файл ' . $file['name']];
    }

    /**
     * @param string $filename
     * @return null
     */
    public function deleteFiles($filename)
    {
        $filesystem = $this->container->get('knp_gaufrette.filesystem_map')->get('originals');

        if ($filesystem->has($filename))
        {
            $filesystem->delete($filename);
        }

        $imageConfigs = $this->container->getParameter('image_dirs');

        foreach ($imageConfigs as $key => $config)
        {
            $filesystem = $this->container->get('knp_gaufrette.filesystem_map')->get($key);

            if ($filesystem->has($filename))
            {
                $filesystem->delete($filename);
            }
        }

        return null;
    }

    /**
     * @param array $file
     * @return mixed|null
     */
    public function getExtension($file)
    {
        if (gettype($file) !== 'array')
        {
            return null;
        }

        if (
            (isset($file['name'])) &&
            (isset(pathinfo($file['name'])['extension']))
        )
        {
            return pathinfo($file['name'])['extension'];
        } elseif (isset($file['tmp_name'])) {
            $extensions = [
                'image/png' => 'png',
                'image/jpeg' => 'jpg',
                'image/gif' => 'gif',
                'image/bmp' => 'bmp',
                'image/vnd.microsoft.icon' => 'ico',
                'image/tiff' => 'tif',
                'image/svg+xml' => 'svg',
            ];

            return $extensions[mime_content_type($file['tmp_name'])] ?? null;
        }

        return null;
    }

    /**
     * @return string
     */
    private function generateRandomFileName()
    {
        $pattern = [
            '0123456789',
            'abcdefghijklmnopqrstuvwxyz',
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        ];

        shuffle($pattern);

        $length = $this->container->hasParameter('image_file_name_length') ? $this->container->getParameter('image_file_name_length') : 8;

        $password = '';
        $count = 0;
        $switch = 0;
        $index = mt_rand(0, 2);

        while ($count < $length)
        {
            $charactersLength = strlen($pattern[$index]);
            $password .= $pattern[$index][mt_rand(0, $charactersLength - 1)];

            $count++;

            if ($count >= $switch)
            {
                $index = ++$index % 3;
                $switch = $count + mt_rand(0, 2);
            }
        }

        return $password;
    }

}
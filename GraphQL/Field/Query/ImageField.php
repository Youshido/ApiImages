<?php
/**
 * Date: 12.10.16
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\ImagesBundle\GraphQL\Field\Query;


use Youshido\GraphQL\Config\Field\FieldConfig;
use Youshido\GraphQL\Execution\ResolveInfo;
use Youshido\GraphQL\Field\AbstractField;
use Youshido\GraphQL\Type\AbstractType;
use Youshido\GraphQL\Type\NonNullType;
use Youshido\GraphQL\Type\Object\AbstractObjectType;
use Youshido\GraphQL\Type\Scalar\IntType;
use Youshido\ImagesBundle\Document\EmbeddedImage;
use Youshido\ImagesBundle\Document\Interfaces\ImageableInterface as ODMImageableInterface;
use Youshido\ImagesBundle\Entity\Interfaces\ImageableInterface as ORMImageableInterface;
use Youshido\ImagesBundle\GraphQL\Type\ImageType;
use Youshido\ImagesBundle\GraphQL\Type\ResizeImageModeType;
use Youshido\ImagesBundle\ValueObject\ResizeConfig;

class ImageField extends AbstractField
{
    public function build(FieldConfig $config)
    {
        $config->addArguments([
            'width'  => [
                'type'    => new IntType(),
                'defaultValue' => 0
            ],
            'height' => [
                'type'    => new IntType(),
                'defaultValue' => 0
            ],
            'mode'   => [
                'type'    => new ResizeImageModeType(),
                'defaultValue' => 'outbound'
            ]
        ]);
    }


    public function resolve($value, array $args, ResolveInfo $info)
    {
        if ($value) {
            if ($value instanceof ODMImageableInterface || $value instanceof ORMImageableInterface || (is_object($value) && method_exists($value, 'getImage'))) {
                if ($image = $value->getImage()) {
                    if (!empty($args['width']) || !empty($args['height'])) {
                        $url = $info->getContainer()->get('api_images.resizer')->getPathResolver()->resolveWebResizablePath(
                            new ResizeConfig($args['width'], $args['height'], $args['mode']),
                            $image
                        );
                    } else {
                        $url = $info->getContainer()->get('api_images.path_resolver')->resolveWebPath($image);
                    }

                    return [
                        'id'    => $image instanceof EmbeddedImage ? $image->getReferenceId() : $image->getId(),
                        'url'   => $url,
                        'image' => $image
                    ];                }
            }
        }

        return null;
    }

    /**
     * @return AbstractObjectType|AbstractType
     */
    public function getType()
    {
        return new ImageType();
    }
}
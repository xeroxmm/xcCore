<?php

namespace AppBundle\Safety\Types;

class Content {
    const TYPE_IMAGE      = 1;
    const TYPE_VIDEO      = 2;
    const TYPE_COLLECTION = 3;
    const TYPE_LINK       = 4;
    const TYPE_GIF        = 5;

    const TYPE_ADVERTISEMENT_NET_EXTERN = 30;
    const TYPE_ADVERTISEMENT_NET_INTERN = 31;
    const TYPE_ADVERTISEMENT_TRADE      = 32;
    const TYPE_ADVERTISEMENT_STORE      = 33;

    const TYPE_ADVERTISEMENT_NET_EXTERN_IMAGE      = 51;
    const TYPE_ADVERTISEMENT_NET_EXTERN_VIDEO      = 52;
    const TYPE_ADVERTISEMENT_NET_EXTERN_COLLECTION = 53;
    const TYPE_ADVERTISEMENT_NET_EXTERN_LINK       = 54;
    const TYPE_ADVERTISEMENT_NET_EXTERN_GIF        = 55;

    const TYPE_ADVERTISEMENT_SLUGS = [
        self::TYPE_ADVERTISEMENT_NET_EXTERN => 'extern',
        self::TYPE_ADVERTISEMENT_NET_INTERN => 'intern',
        self::TYPE_ADVERTISEMENT_TRADE => 'trade',
        self::TYPE_ADVERTISEMENT_STORE => 'store',
        self::TYPE_ADVERTISEMENT_NET_EXTERN_IMAGE => 'x_image',
        self::TYPE_ADVERTISEMENT_NET_EXTERN_VIDEO => 'x_video',
        self::TYPE_ADVERTISEMENT_NET_EXTERN_COLLECTION => 'x_collection',
        self::TYPE_ADVERTISEMENT_NET_EXTERN_LINK => 'x_link',
        self::TYPE_ADVERTISEMENT_NET_EXTERN_GIF => 'x_gif'
    ];

    const TYPE_INFO = 100;

    const TYPE_ALL_STRINGS = [
        self::TYPE_IMAGE => 'i',
        self::TYPE_VIDEO => 'v',
        self::TYPE_COLLECTION => 'c',
        self::TYPE_LINK => 'l',
        self::TYPE_GIF => 'g',
        self::TYPE_ADVERTISEMENT_NET_EXTERN => 'out',
        self::TYPE_ADVERTISEMENT_NET_INTERN => 'out',
        self::TYPE_ADVERTISEMENT_TRADE => 'out',
        self::TYPE_ADVERTISEMENT_STORE => 'out',

        self::TYPE_ADVERTISEMENT_NET_EXTERN_IMAGE => 'xi',
        self::TYPE_ADVERTISEMENT_NET_EXTERN_VIDEO => 'xv',
        self::TYPE_ADVERTISEMENT_NET_EXTERN_COLLECTION => 'xc',
        self::TYPE_ADVERTISEMENT_NET_EXTERN_LINK => 'xl',
        self::TYPE_ADVERTISEMENT_NET_EXTERN_GIF => 'xg',

        self::TYPE_COLLECTION => 'c',
        self::TYPE_INFO => '__'
    ];

    /**
     * @param int
     * @return null | string
     */
    static function getTypeString(int $int) {
        return self::TYPE_ALL_STRINGS[$int] ?? NULL;
    }

    static function isAdvertisement(int $int): bool {
        return $int >= 30 && $int < 40;
    }

    static function isAdvertisementSlug(?string $slug): bool {
        return in_array($slug, self::TYPE_ADVERTISEMENT_SLUGS);
    }

    static function getAdvertisementIntBySlug(?string $slug): int {
        foreach (self::TYPE_ADVERTISEMENT_SLUGS as $key => $var) {
            if ($var == $slug)
                return $key;
        }
        return self::TYPE_ADVERTISEMENT_NET_EXTERN;
    }
}
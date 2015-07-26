<?php
/*
 * (c) 2014, Dmitrijs Balabka
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\CoreBundle\Routing;

use Symfony\Bundle\FrameworkBundle\Routing\DelegatingLoader as BaseDelegatingLoader;
use Symfony\Component\Routing\RouteCollection;


class DelegatingLoader extends BaseDelegatingLoader
{
    const PREFIX = '___LB___';
    /**
     * Loads a resource.
     *
     * @param mixed  $resource A resource
     * @param string $type     The resource type
     *
     * @return RouteCollection A RouteCollection instance
     */
    public function load($resource, $type = null)
    {
        $collection = parent::load($resource, $type);

        foreach ($collection->all() as $name => $route) {
            $route->setDefault('_locale', 'en');
            if ($route->getPath() === '/') {
                $route->setPath('/{_locale}');
            } else {
                $route->setPath('/{_locale}' . $route->getPath());
            }
            $route->setRequirement('_locale', 'en|lv|ru');
        }

        return $collection;
    }
}
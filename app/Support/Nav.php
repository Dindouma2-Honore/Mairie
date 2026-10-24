<?php

namespace App\Support;

use App\Models\User;

/**
 * Construit le menu latéral (accordéons) filtré selon les droits de l'utilisateur.
 */
class Nav
{
    public static function for(?User $user): array
    {
        $groups = [];

        foreach (config('navigation') as $group) {
            // Entrée simple (sans sous-menu), ex. tableau de bord.
            if (isset($group['route'])) {
                $group['active'] = self::matches($group['match'] ?? []);
                $group['url'] = route($group['route']);
                $groups[] = $group;
                continue;
            }

            $items = [];
            foreach ($group['items'] as $item) {
                if (! empty($item['soon'])) {
                    $items[] = $item + ['active' => false];
                    continue;
                }
                if (isset($item['can']) && ! self::allowed($user, $item['can'])) {
                    continue;
                }
                $item['url'] = route($item['route']);
                $item['active'] = self::matches($item['match'] ?? []);
                $items[] = $item;
            }

            // Un module sans aucune entrée accessible n'est pas affiché.
            $usable = array_filter($items, fn ($i) => empty($i['soon']));
            if (empty($group['soon']) && ! $usable) {
                continue;
            }

            $group['items'] = $items;
            $group['open'] = (bool) array_filter($items, fn ($i) => $i['active']);
            $groups[] = $group;
        }

        return $groups;
    }

    private static function allowed(?User $user, string|array $can): bool
    {
        if (! $user) {
            return false;
        }
        foreach ((array) $can as $permission) {
            if ($user->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    private static function matches(array $patterns): bool
    {
        return $patterns !== [] && request()->routeIs(...$patterns);
    }
}

<?php

namespace Packstub\Nitpick\Contracts;

/**
 * A theme that tells the picker how its panel looks, for the small light and
 * dark preview on its card. Optional: without it, the preview is drawn from
 * the theme's palette (its gray and primary colours). Return only what the
 * palette gets wrong: a glass theme's tinted background, a gradient button.
 *
 *     public function getPreview(): array
 *     {
 *         return [
 *             'light' => ['background' => 'linear-gradient(135deg, #e0f2fe, #fce7f3)'],
 *             'dark' => ['background' => '#060914', 'surface' => '#0b1020'],
 *         ];
 *     }
 *
 * Each mode takes any of `background` (the page), `sidebar`, `surface` (a
 * card), `border`, `primary` (a button) and `text`: CSS colours, or any CSS
 * background such as a gradient.
 */
interface HasPreview
{
    /**
     * @return array{light?: array<string, string>, dark?: array<string, string>}
     */
    public function getPreview(): array;
}

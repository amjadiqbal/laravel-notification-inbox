<?php

namespace AmjadIqbal\NotificationInbox\Support;

use Illuminate\Support\HtmlString;

/**
 * Renders the text formats the channels use as safe HTML. Everything is
 * escaped first and only a small set of formatting is turned back on, so a
 * captured payload can never inject markup into the dashboard. Links only
 * become anchors for http(s) and mailto URLs.
 */
final class Markup
{
    /** @var list<string> generated HTML kept aside while formatting rules run */
    private array $stash = [];

    /**
     * Slack mrkdwn: *bold*, _italic_, ~strike~, `code`, ```pre```, <url|label>, <url>.
     */
    public static function slack(string $text): HtmlString
    {
        $m = new self;
        $html = $m->escape($text);
        $html = $m->protect($html, '/```(.+?)```/s', fn (array $x): string => '<pre>'.trim($x[1], "\n").'</pre>');
        $html = $m->protect($html, '/`([^`\n]+)`/', fn (array $x): string => '<code>'.$x[1].'</code>');
        $html = $m->protect($html, '/&lt;((?:https?:\/\/|mailto:)[^|&]+?)(?:\|(.+?))?&gt;/', fn (array $x): string => self::link($x[1], $x[2] ?? $x[1]));
        $html = $m->protect($html, '/&lt;[@#!]([^&]+?)&gt;/', fn (array $x): string => '<span class="ni-mention">@'.$x[1].'</span>');
        $html = $m->rules($html, [
            '/(?<![\w*])\*(?!\s)(.+?)(?<!\s)\*(?![\w*])/s' => '<strong>$1</strong>',
            '/(?<![\w_])_(?!\s)(.+?)(?<!\s)_(?![\w_])/s' => '<em>$1</em>',
            '/(?<![\w~])~(?!\s)(.+?)(?<!\s)~(?![\w~])/s' => '<del>$1</del>',
        ]);

        return new HtmlString(nl2br($m->restore($html), false));
    }

    /**
     * Markdown as Discord, Teams and Telegram (Markdown / MarkdownV2) use it:
     * **bold**, *italic*, __underline__, _italic_, ~~strike~~, `code`, [label](url).
     * Telegram's legacy Markdown treats a single *star* as bold.
     */
    public static function markdown(string $text, bool $singleStarIsBold = false): HtmlString
    {
        $m = new self;
        $html = $m->escape($text);
        $html = $m->protect($html, '/```(?:[a-z0-9]*\n)?(.+?)```/s', fn (array $x): string => '<pre>'.trim($x[1], "\n").'</pre>');
        $html = $m->protect($html, '/`([^`\n]+)`/', fn (array $x): string => '<code>'.$x[1].'</code>');
        $html = $m->protect($html, '/\[([^\]]+)\]\(((?:https?:\/\/|mailto:)[^)\s]+)\)/', fn (array $x): string => self::link($x[2], $x[1]));
        $html = $m->protect($html, '/(?<![="\w])((?:https?:\/\/)[^\s<]+[^\s<.,!?)])/', fn (array $x): string => self::link($x[1], $x[1]));
        $html = $m->rules($html, [
            '/\*\*(?!\s)(.+?)(?<!\s)\*\*/s' => '<strong>$1</strong>',
            '/__(?!\s)(.+?)(?<!\s)__/s' => '<u>$1</u>',
            '/~~(?!\s)(.+?)(?<!\s)~~/s' => '<del>$1</del>',
            '/(?<![\w*\\\\])\*(?!\s)(.+?)(?<!\s|\\\\)\*(?![\w*])/s' => $singleStarIsBold ? '<strong>$1</strong>' : '<em>$1</em>',
            '/(?<![\w_\\\\])_(?!\s)(.+?)(?<!\s|\\\\)_(?![\w_])/s' => '<em>$1</em>',
            '/(?<![\w~\\\\])~(?!\s)(.+?)(?<!\s|\\\\)~(?![\w~])/s' => '<del>$1</del>',
            '/\|\|(.+?)\|\|/s' => '<span class="ni-spoiler">$1</span>',
        ]);
        // MarkdownV2 escapes such as \. \! \- show as the plain character.
        $html = preg_replace('/\\\\([_*\[\]()~`>#+\-=|{}.!\\\\])/', '$1', $html) ?? $html;

        return new HtmlString(nl2br($m->restore($html), false));
    }

    /**
     * Telegram's HTML parse mode allows a fixed tag list; keep those, escape the rest.
     */
    public static function telegramHtml(string $text): HtmlString
    {
        $m = new self;
        $html = $m->escape($text);
        $html = $m->protect($html, '/&lt;a href=&quot;((?:https?:\/\/|mailto:)[^&]+?)&quot;&gt;(.*?)&lt;\/a&gt;/is', fn (array $x): string => self::link($x[1], $x[2]));
        $html = preg_replace('/&lt;(\/?)(b|strong|i|em|u|ins|s|strike|del|code|pre|blockquote)(?:\s[^&]*)?&gt;/i', '<$1$2>', $html) ?? $html;
        $html = preg_replace('/&lt;(\/?)tg-spoiler&gt;/i', '<$1span>', $html) ?? $html;

        return new HtmlString(nl2br($m->restore($html), false));
    }

    /**
     * Plain text with markup characters removed, for summaries and search.
     */
    public static function plain(string $text): string
    {
        $text = preg_replace('/<((?:https?:\/\/|mailto:)[^|>]+)\|([^>]+)>/', '$2', $text) ?? $text;
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text) ?? $text;
        $text = strip_tags($text);
        $text = preg_replace('/(\*\*|__|~~|\|\||```|`)/', '', $text) ?? $text;
        $text = preg_replace('/(?<![\w\\\\])[*_~](?=\S)|(?<=\S)(?<!\\\\)[*_~](?!\w)/', '', $text) ?? $text;
        $text = preg_replace('/\\\\([_*\[\]()~`>#+\-=|{}.!])/', '$1', $text) ?? $text;

        return trim(html_entity_decode($text, ENT_QUOTES | ENT_HTML5));
    }

    /**
     * An anchor for http(s)/mailto URLs; anything else stays plain text. $label is already escaped.
     */
    public static function link(string $url, string $label): string
    {
        $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5);

        if (! preg_match('#^(https?://|mailto:)#i', $url)) {
            return $label;
        }

        return '<a href="'.htmlspecialchars($url, ENT_QUOTES | ENT_HTML5, 'UTF-8').'" target="_blank" rel="noopener noreferrer">'.$label.'</a>';
    }

    /**
     * An image URL that is safe to put in src: http(s), or a path on this app.
     */
    public static function imageUrl(mixed $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        return preg_match('#^(https?://|/(?!/))#i', $url) ? $url : null;
    }

    private function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Replace matches with placeholders so later rules can't touch them.
     *
     * @param  callable(array<int|string, string>): string  $render
     */
    private function protect(string $html, string $pattern, callable $render): string
    {
        return preg_replace_callback($pattern, function (array $match) use ($render): string {
            $this->stash[] = $render($match);

            return "\x00".(count($this->stash) - 1)."\x00";
        }, $html) ?? $html;
    }

    /**
     * @param  array<string, string>  $rules
     */
    private function rules(string $html, array $rules): string
    {
        foreach ($rules as $pattern => $replacement) {
            $html = preg_replace($pattern, $replacement, $html) ?? $html;
        }

        return $html;
    }

    private function restore(string $html): string
    {
        // Stashed items can contain other placeholders (a link label with code), so repeat.
        for ($i = 0; $i < 3 && str_contains($html, "\x00"); $i++) {
            $html = preg_replace_callback("/\x00(\d+)\x00/", fn (array $m): string => $this->stash[(int) $m[1]] ?? '', $html) ?? $html;
        }

        return $html;
    }
}

<?php

use AmjadIqbal\NotificationInbox\Support\Markup;

it('renders Slack mrkdwn safely', function () {
    expect((string) Markup::slack("*bold* _it_ ~gone~ `co*de*` <https://x.test/a_b_c|my_link> <@U123>\n<script>"))
        ->toBe('<strong>bold</strong> <em>it</em> <del>gone</del> <code>co*de*</code> <a href="https://x.test/a_b_c" target="_blank" rel="noopener noreferrer">my_link</a> <span class="ni-mention">@U123</span><br>'."\n".'&lt;script&gt;');
});

it('renders Markdown with links and keeps underscores in URLs', function () {
    expect((string) Markup::markdown('**B** *i* __u__ ~~s~~ [L](https://x.test/a_b) see https://y.test/p_q.'))
        ->toBe('<strong>B</strong> <em>i</em> <u>u</u> <del>s</del> <a href="https://x.test/a_b" target="_blank" rel="noopener noreferrer">L</a> see <a href="https://y.test/p_q" target="_blank" rel="noopener noreferrer">https://y.test/p_q</a>.');
});

it('treats a single star as bold for Telegram and unescapes MarkdownV2', function () {
    expect((string) Markup::markdown('*Order shipped*\. Done\!', singleStarIsBold: true))->toBe('<strong>Order shipped</strong>. Done!');
});

it('never links javascript: urls', function () {
    expect((string) Markup::markdown('[x](javascript:alert(1))'))->not->toContain('<a ')
        ->and((string) Markup::slack('<javascript:alert(1)|x>'))->not->toContain('<a ')
        ->and((string) Markup::telegramHtml('<a href="javascript:alert(1)">x</a>'))->not->toContain('<a ');
});

it('keeps Telegram HTML tags from the allowed list only', function () {
    expect((string) Markup::telegramHtml('<b>hi</b> <i>x</i> <a href="https://t.test">l</a> <img src=x onerror=alert(1)>'))
        ->toBe('<b>hi</b> <i>x</i> <a href="https://t.test" target="_blank" rel="noopener noreferrer">l</a> &lt;img src=x onerror=alert(1)&gt;');
});

it('strips markup to plain text', function () {
    expect(Markup::plain('*Order* _shipped_ <https://x.test|track> [here](https://y.test) `JD1` file_name.txt'))
        ->toBe('Order shipped track here JD1 file_name.txt');
});

it('only allows http(s) and app paths as image sources', function () {
    expect(Markup::imageUrl('https://x.test/a.png'))->toBe('https://x.test/a.png')
        ->and(Markup::imageUrl('/icon.png'))->toBe('/icon.png')
        ->and(Markup::imageUrl('//evil.test/a.png'))->toBeNull()
        ->and(Markup::imageUrl('javascript:alert(1)'))->toBeNull()
        ->and(Markup::imageUrl(null))->toBeNull();
});

it('renders Telegram legacy Markdown without word-boundary rules', function () {
    expect((string) Markup::telegramMarkdown('*Order A-7 shipped*Arriving _Friday_. `JD_1` [Track](https://t.test/a_b)'))
        ->toBe('<strong>Order A-7 shipped</strong>Arriving <em>Friday</em>. <code>JD_1</code> <a href="https://t.test/a_b" target="_blank" rel="noopener noreferrer">Track</a>')
        ->and(Markup::telegramPlain('*Order A-7 shipped*Arriving _Friday_.'))->toBe('Order A-7 shippedArriving Friday.');
});

it('renders Telegram MarkdownV2 with its escapes', function () {
    expect((string) Markup::telegramMarkdown('*Total\\* 5\\.00* __u__ ~s~ ||secret|| \\> quote <b>', v2: true))
        ->toBe('<strong>Total* 5.00</strong> <u>u</u> <del>s</del> <span class="ni-spoiler">secret</span> &gt; quote &lt;b&gt;');
});

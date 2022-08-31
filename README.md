# MdGen

[![Tests](https://github.com/Gashmob/MdGen/actions/workflows/test.yml/badge.svg)](https://github.com/Gashmob/MdGen/actions/workflows/test.yml)
[![wakatime](https://wakatime.com/badge/user/c1e2386d-065c-4366-b163-d98f957273dc/project/44c9d956-4bea-471c-8dc6-5752f533022a.svg)](https://wakatime.com/badge/user/c1e2386d-065c-4366-b163-d98f957273dc/project/44c9d956-4bea-471c-8dc6-5752f533022a)

Markdown template engine. This php library will generate html from markdown templates (`*.mdt` file).

- [Usage](#usage)
  - [Templates format](#templates-format)
  - [Special](#special)
    - [Base template](#base-template)
    - [Include template](#include-template)
  - [Generate html](#generate-html)
- [Installation](#installation)

## Usage

Let see how to use MdGen. If you want some examples, you can go inside test dir, there is the template with the html result.

### Templates format

First, there is all the format that you can use for your template and how it will be translated to html. Essentially it's markdown, but there is some difference :

<table>
<tr><th>MdGen</th><th>html</th></tr>
<tr>
<td>

```md
# Title 1
```

</td>
<td>

```html
<h1>Title 1</h1>
```

</td>
</tr>
<tr>
<td>

```md
## Title 2
```

</td>
<td>

```html
<h2>Title 2</h2>
```

</td>
</tr>
<tr>
<td>

```md
### Title 3
```

</td>
<td>

```html
<h3>Title 3</h3>
```

</td>
</tr>
<tr>
<td>

```md
#### Title 4
```

</td>
<td>

```html
<h4>Title 4</h4>
```

</td>
</tr>
<tr>
<td>

```md
##### Title 5
```

</td>
<td>

```html
<h5>Title 5</h5>
```

</td>
</tr>
<tr>
<td>

```md
###### Title 6
```

</td>
<td>

```html
<h6>Title 6</h6>
```

</td>
</tr>
<tr>
<td>

```md
Some text
```

</td>
<td>

```html
<p>Some text</p>
```

</td>
</tr>
<tr>
<td>

```md
[Google](https://www.google.com)
```

</td>
<td>

```html
<a href="https://www.google.com">Google</a>
```

</td>
</tr>
<tr>
<td>

```md
![image](http://image.com/a.png)
```

</td>
<td>

```html
<img src="http://image.com/a.png" alt="image"/>
```

</td>
</tr>
<tr>
<td>

```md
**Bold**
```

</td>
<td>

```html
<strong>Bold</strong>
```

</td>
</tr>
<tr>
<td>

```md
*Italic*
```

</td>
<td>

```html
<em>Italic</em>
```

</td>
</tr>
<tr>
<td>

```md
1. First item
2. Second item
```

</td>
<td>

```html
<ol>
    <li>First item</li>
    <li>Second item</li>
</ol>
```

</td>
</tr>
<tr>
<td>

```md
- First item
- Second item
```

</td>
<td>

```html
<ul>
    <li>First item</li>
    <li>Second item</li>
</ul>
```

</td>
</tr>
<tr>
<td>

```md
---
```

</td>
<td>

```html
<hr/>
```

</td>
</tr>
<tr>
<td>

```md
`code`
```

</td>
<td>

```html
<code>code</code>
```

</td>
</tr>
<tr>
<td>

```md
> quote
```

</td>
<td>

```html
<blockquote>
    quote
</blockquote>
```

</td>
</tr>
<tr>
<td>

```md
| Col 1 | Col 2 | Col 3 |
| :---- | :---: | ----: |
| 1     |   2   |     3 |
```

</td>
<td>

```html
<table>
    <thead>
        <tr>
            <th style="text-align:left;">Col 1</th>
            <th style="text-align:center;">Col 2</th>
            <th style="text-align:right;">Col 3</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>1</td>
            <td>2</td>
            <td>3</td>
        </tr>
    </tbody>
</table>
```

</td>
</tr>
<tr>
<td>

```md
<div>Some html</div>
<div># Title</div>
```

</td>
<td>

```html
<div>Some html</div>
<div><h1>Title</h1></div>
```

</td>
</tr>
<tr>
<td>

```md
{foo}
```

</td>
<td>

```html
bar
```

Replace `{foo}` by the value gived at the render function :

```php
$engine->('myTemplate.mdt', [ "foo" => "bar" ])
```

</td>
</tr>
</table>

### Special

But there is some another statements that can be used in your template.

The first of generation is pre-rendering. During this steps the library look at the first lines for a special statement. These lines specify some values that the pre-render function should return. It will not appear in final html document. It works on a key value system :

```md
[#]: key -> value
```

The pre-render function will then return :

```php
[
    "key" => "value",
]
```

#### Base template

Frequently your templates need the same base in html (same header, same footer, ...). For that you can add this statement at the beginning of your template (after the key-value)

```md
[#]: base someTemplate
```

The library will then looking for the file `someTemplate.mdt` from where the template is located. You can override this by providing a search path to the library :

```php
$engine->basePath('someWhere/');
```

In the file `someTemplate.mdt` you can write all you want. You just need to add the statement below to indicate where to include the calling template.

```md
[#]: baseInclude
```

Note that you can add this statement as much as you want, it will just include the html at each place.

#### Include template

You can also include another template in your template. For that you just have to write :

```md
[#]: include someTemplate
```

It will then looking for the file `someTemplate.mdt` from where the template is located. You can override this by providing a search path to the library :

```php
$engine->includePath('someWhere');
```

### Generate html 

Finally, there is how to use the engine to generate html document from a template file :

```php
use Gashmob\MdGen\MdGenEngine;

// Create a new instance of the engine
$engine = new MdGenEngine();

// Set base and include paths
$engine->basePath('bases/');
$engine->includePath('includes/');

// Pre-render template (this is optional)
$array = $engine->preRender('myTemplate.mdt');
/* Compute some values from $array */

// Render template
$html = $engine->render('myTemplate.mdt', [
    "foo" => "bar",
]);
```

## Installation

The easiest way to use this library is to pass from composer with :

```console
composer require gashmob/mdgen
```
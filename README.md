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

## Usage

Let see how to use MdGen :

### Templates format

First, there is all the format that you can use for your template and how it will be translated to html. Essentially is markdown, but there is some difference :

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
</table>

### Special

TODO: explain special statements for preRender and render

#### Base template

TODO: explain how to use base template.

#### Include template

TODO: explain how to include another template.

### Generate html 

TODO: explain how to use library to generate html from `*.mdt`.

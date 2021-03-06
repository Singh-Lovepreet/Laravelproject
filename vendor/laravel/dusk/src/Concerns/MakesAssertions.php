<?php

namespace Laravel\Dusk\Concerns;

use Illuminate\Support\Str;
use PHPUnit\Framework\Assert as PHPUnit;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\Exception\NoSuchElementException;

trait MakesAssertions
{
    /**
     * Assert that the page title is the given value.
     *
     * @param  string  $title
     * @return $this
     */
    public function assertTitle($title)
    {
        PHPUnit::assertEquals(
            $title, $this->driver->getTitle(),
            "Expected title [{$title}] does not equal actual title [{$this->driver->getTitle()}]."
        );

        return $this;
    }

    /**
     * Assert that the page title contains the given value.
     *
     * @param  string  $title
     * @return $this
     */
    public function assertTitleContains($title)
    {
        PHPUnit::assertTrue(
            Str::contains($this->driver->getTitle(), $title),
            "Did not see expected value [{$title}] within title [{$this->driver->getTitle()}]."
        );

        return $this;
    }

    /**
     * Assert that the given cookie is present.
     *
     * @param  string $name
     * @param  bool  $decrypt
     * @return $this
     */
    public function assertHasCookie($name, $decrypt = true)
    {
        $cookie = $decrypt ? $this->cookie($name) : $this->plainCookie($name);

        PHPUnit::assertTrue(
            ! is_null($cookie),
            "Did not find expected cookie [{$name}]."
        );

        return $this;
    }

    /**
     * Assert that the given plain cookie is present.
     *
     * @param  string  $name
     * @return $this
     */
    public function assertHasPlainCookie($name)
    {
        return $this->assertHasCookie($name, false);
    }

    /**
     * Assert that the given cookie is not present.
     *
     * @param  string $name
     * @param  bool  $decrypt
     * @return $this
     */
    public function assertCookieMissing($name, $decrypt = true)
    {
        $cookie = $decrypt ? $this->cookie($name) : $this->plainCookie($name);

        PHPUnit::assertTrue(
            is_null($cookie),
            "Found unexpected cookie [{$name}]."
        );

        return $this;
    }

    /**
     * Assert that the given plain cookie is not present.
     *
     * @param  string  $name
     * @return $this
     */
    public function assertPlainCookieMissing($name)
    {
        return $this->assertCookieMissing($name, false);
    }

    /**
     * Assert that an encrypted cookie has a given value.
     *
     * @param  string  $name
     * @param  string  $value
     * @param  bool  $decrypt
     * @return $this
     */
    public function assertCookieValue($name, $value, $decrypt = true)
    {
        $actual = $decrypt ? $this->cookie($name) : $this->plainCookie($name);

        PHPUnit::assertEquals(
            $value, $actual,
            "Cookie [{$name}] had value [{$actual}], but expected [{$value}]."
        );

        return $this;
    }

    /**
     * Assert that a cookie has a given value.
     *
     * @param  string  $name
     * @param  string  $value
     * @return $this
     */
    public function assertPlainCookieValue($name, $value)
    {
        return $this->assertCookieValue($name, $value, false);
    }

    /**
     * Assert that the given text appears on the page.
     *
     * @param  string  $text
     * @return $this
     */
    public function assertSee($text)
    {
        return $this->assertSeeIn('', $text);
    }

    /**
     * Assert that the given text does not appear on the page.
     *
     * @param  string  $text
     * @return $this
     */
    public function assertDontSee($text)
    {
        return $this->assertDontSeeIn('', $text);
    }

    /**
     * Assert that the given text appears within the given selector.
     *
     * @param  string  $selector
     * @param  string  $text
     * @return $this
     */
    public function assertSeeIn($selector, $text)
    {
        $fullSelector = $this->resolver->format($selector);

        $element = $this->resolver->findOrFail($selector);

        PHPUnit::assertTrue(
            Str::contains($element->getText(), $text),
            "Did not see expected text [{$text}] within element [{$fullSelector}]."
        );

        return $this;
    }

    /**
     * Assert that the given text does not appear within the given selector.
     *
     * @param  string  $selector
     * @param  string  $text
     * @return $this
     */
    public function assertDontSeeIn($selector, $text)
    {
        $fullSelector = $this->resolver->format($selector);

        $element = $this->resolver->findOrFail($selector);

        PHPUnit::assertFalse(
            Str::contains($element->getText(), $text),
            "Saw unexpected text [{$text}] within element [{$fullSelector}]."
        );

        return $this;
    }

    /**
     * Assert that the given source code is present on the page.
     *
     * @param  string  $code
     * @return $this
     */
    public function assertSourceHas($code)
    {
        PHPUnit::assertTrue(
            Str::contains($this->driver->getPageSource(), $code),
            "Did not find expected source code [{$code}]"
        );

        return $this;
    }

    /**
     * Assert that the given source code is not present on the page.
     *
     * @param  string  $code
     * @return $this
     */
    public function assertSourceMissing($code)
    {
        PHPUnit::assertFalse(
            Str::contains($this->driver->getPageSource(), $code),
            "Found unexpected source code [{$code}]"
        );

        return $this;
    }

    /**
     * Assert that the given link is visible.
     *
     * @param  string  $link
     * @return $this
     */
    public function assertSeeLink($link)
    {
        if ($this->resolver->prefix) {
            $message = "Did not see expected link [{$link}] within [{$this->resolver->prefix}].";
        } else {
            $message = "Did not see expected link [{$link}].";
        }

        PHPUnit::assertTrue(
            $this->seeLink($link),
            $message
        );

        return $this;
    }

    /**
     * Assert that the given link is not visible.
     *
     * @param  string  $link
     * @return $this
     */
    public function assertDontSeeLink($link)
    {
        if ($this->resolver->prefix) {
            $message = "Saw unexpected link [{$link}] within [{$this->resolver->prefix}].";
        } else {
            $message = "Saw unexpected link [{$link}].";
        }

        PHPUnit::assertFalse(
            $this->seeLink($link),
            $message
        );

        return $this;
    }

    /**
     * Determine if the given link is visible.
     *
     * @param  string  $link
     * @return bool
     */
    public function seeLink($link)
    {
        $this->ensurejQueryIsAvailable();

        $selector = addslashes(trim($this->resolver->format("a:contains('{$link}')")));

        $script = <<<JS
            var link = jQuery.find("{$selector}");
            return link.length > 0 && jQuery(link).is(':visible');
JS;

        return $this->driver->executeScript($script);
    }

    /**
     * Assert that the given input or text area contains the given value.
     *
     * @param  string  $field
     * @param  string  $value
     * @return $this
     */
    public function assertInputValue($field, $value)
    {
        PHPUnit::assertEquals(
            $value, $this->inputValue($field),
            "Expected value [{$value}] for the [{$field}] input does not equal the actual value [{$this->inputValue($field)}]."
        );

        return $this;
    }

    /**
     * Assert that the given input or text area does not contain the given value.
     *
     * @param  string  $field
     * @param  string  $value
     * @return $this
     */
    public function assertInputValueIsNot($field, $value)
    {
        PHPUnit::assertNotEquals(
            $value, $this->inputValue($field),
            "Value [{$value}] for the [{$field}] input should not equal the actual value."
        );

        return $this;
    }

    /**
     * Get the value of the given input or text area field.
     *
     * @param  string  $field
     * @return string
     */
    public function inputValue($field)
    {
        $element = $this->resolver->resolveForTyping($field);

        return in_array($element->getTagName(), ['input', 'textarea'])
                        ? $element->getAttribute('value')
                        : $element->getText();
    }

    /**
     * Assert that the given checkbox field is checked.
     *
     * @param  string  $field
     * @param  string  $value
     * @return $this
     */
    public function assertChecked($field, $value = null)
    {
        $element = $this->resolver->resolveForChecking($field, $value);

        PHPUnit::assertTrue(
            $element->isSelected(),
            "Expected checkbox [{$field}] to be checked, but it wasn't."
        );

        return $this;
    }

    /**
     * Assert that the given checkbox field is not checked.
     *
     * @param  string  $field
     * @param  string  $value
     * @return $this
     */
    public function assertNotChecked($field, $value = null)
    {
        $element = $this->resolver->resolveForChecking($field, $value);

        PHPUnit::assertFalse(
            $element->isSelected(),
            "Checkbox [{$field}] was unexpectedly checked."
        );

        return $this;
    }

    /**
     * Assert that the given radio field is selected.
     *
     * @param  string  $field
     * @param  string  $value
     * @return $this
     */
    public function assertRadioSelected($field, $value)
    {
        $element = $this->resolver->resolveForRadioSelection($field, $value);

        PHPUnit::assertTrue(
            $element->isSelected(),
            "Expected radio [{$field}] to be selected, but it wasn't."
        );

        return $this;
    }

    /**
     * Assert that the given radio field is not selected.
     *
     * @param  string  $field
     * @param  string  $value
     * @return $this
     */
    public function assertRadioNotSelected($field, $value = null)
    {
        $element = $this->resolver->resolveForRadioSelection($field, $value);

        PHPUnit::assertFalse(
            $element->isSelected(),
            "Radio [{$field}] was unexpectedly selected."
        );

        return $this;
    }

    /**
     * Assert that the given select field has the given value selected.
     *
     * @param  string  $field
     * @param  string  $value
     * @return $this
     */
    public function assertSelected($field, $value)
    {
        PHPUnit::assertTrue(
            $this->selected($field, $value),
            "Expected value [{$value}] to be selected for [{$field}], but it wasn't."
        );

        return $this;
    }

    /**
     * Assert that the given select field does not have the given value selected.
     *
     * @param  string  $field
     * @param  string  $value
     * @return $this
     */
    public function assertNotSelected($field, $value)
    {
        PHPUnit::assertFalse(
            $this->selected($field, $value),
            "Unexpected value [{$value}] selected for [{$field}]."
        );

        return $this;
    }

    /**
     * Assert that the given array of values are available to be selected.
     *
     * @param string  $field
     * @param array  $values
     * @return $this
     */
    public function assertSelectHasOptions($field, array $values)
    {
        $options = $this->resolver->resolveSelectOptions($field, $values);

        $options = collect($options)->unique(function (RemoteWebElement $option) {
            return $option->getAttribute('value');
        })->all();

        PHPUnit::assertCount(
            count($values), $options,
            "Expected options [".implode(',', $values)."] for selection field [{$field}] to be available."
        );

        return $this;
    }

    /**
     * Assert that the given array of values are not available to be selected.
     *
     * @param string  $field
     * @param array  $values
     * @return $this
     */
    public function assertSelectMissingOptions($field, array $values)
    {
        PHPUnit::assertCount(
            0, $this->resolver->resolveSelectOptions($field, $values),
            "Unexpected options [".implode(',', $values)."] for selection field [{$field}]."
        );

        return $this;
    }

    /**
     * Assert that the given value is available to be selected on the given field.
     *
     * @param string  $field
     * @param string  $value
     * @return $this
     */
    public function assertSelectHasOption($field, $value)
    {
        return $this->assertSelectHasOptions($field, [$value]);
    }

    /**
     * Assert that the given value is not available to be selected on the given field.
     *
     * @param string  $field
     * @param string  $value
     * @return $this
     */
    public function assertSelectMissingOption($field, $value)
    {
        return $this->assertSelectMissingOptions($field, [$value]);
    }

    /**
     * Determine if the given value is selected for the given select field.
     *
     * @param  string  $field
     * @param  string  $value
     * @return bool
     */
    public function selected($field, $value)
    {
        $options = $this->resolver->resolveSelectOptions($field, (array) $value);

        return collect($options)->contains(function (RemoteWebElement $option) {
            return $option->isSelected();
        });
    }

    /**
     * Assert that the element at the given selector has the given value.
     *
     * @param  string  $selector
     * @param  string  $value
     * @return $this
     */
    public function assertValue($selector, $value)
    {
        $actual = $this->resolver->findOrFail($selector)->getAttribute('value');

        PHPUnit::assertEquals($value, $actual);

        return $this;
    }

    /**
     * Assert that the element with the given selector is visible.
     *
     * @param  string  $selector
     * @return $this
     */
    public function assertVisible($selector)
    {
        $fullSelector = $this->resolver->format($selector);

        PHPUnit::assertTrue(
            $this->resolver->findOrFail($selector)->isDisplayed(),
            "Element [{$fullSelector}] is not visible."
        );

        return $this;
    }

    /**
     * Assert that the element with the given selector is present in the DOM.
     *
     * @param  string  $selector
     * @return $this
     */
    public function assertPresent($selector)
    {
        $fullSelector = $this->resolver->format($selector);

        PHPUnit::assertTrue(
            ! is_null($this->resolver->find($selector)),
            "Element [{$fullSelector}] is not present."
        );

        return $this;
    }

    /**
     * Assert that the element with the given selector is not on the page.
     *
     * @param  string  $selector
     * @return $this
     */
    public function assertMissing($selector)
    {
        $fullSelector = $this->resolver->format($selector);

        try {
            $missing = ! $this->resolver->findOrFail($selector)->isDisplayed();
        } catch (NoSuchElementException $e) {
            $missing = true;
        }

        PHPUnit::assertTrue($missing, "Saw unexpected element [{$fullSelector}].");

        return $this;
    }

    /**
     * Assert that a JavaScript dialog with given message has been opened.
     *
     * @param  string  $message
     * @return $this
     */
    public function assertDialogOpened($message)
    {
        $actualMessage = $this->driver->switchTo()->alert()->getText();

        PHPUnit::assertEquals(
            $message, $actualMessage,
            "Expected dialog message [{$message}] does not equal actual message [{$actualMessage}]."
        );

        return $this;
    }

    /**
     * Assert that the given field is enabled.
     *
     * @param  string  $field
     * @return $this
     */
    public function assertEnabled($field)
    {
        $element = $this->resolver->resolveForField($field);

        PHPUnit::assertTrue(
            $element->isEnabled(),
            "Expected element [{$field}] to be enabled, but it wasn't."
        );

        return $this;
    }

    /**
     * Assert that the given field is disabled.
     *
     * @param  string  $field
     * @return $this
     */
    public function assertDisabled($field)
    {
        $element = $this->resolver->resolveForField($field);

        PHPUnit::assertFalse(
            $element->isEnabled(),
            "Expected element [{$field}] to be disabled, but it wasn't."
        );

        return $this;
    }

    /**
     * Assert that the given field is focused.
     *
     * @param  string  $field
     * @return $this
     */
    public function assertFocused($field)
    {
        $element = $this->resolver->resolveForField($field);

        PHPUnit::assertTrue(
            $this->driver->switchTo()->activeElement()->equals($element),
            "Expected element [{$field}] to be focused, but it wasn't."
        );

        return $this;
    }

    /**
     * Assert that the given field is not focused.
     *
     * @param  string  $field
     * @return $this
     */
    public function assertNotFocused($field)
    {
        $element = $this->resolver->resolveForField($field);

        PHPUnit::assertFalse(
            $this->driver->switchTo()->activeElement()->equals($element),
            "Expected element [{$field}] not to be focused, but it was."
        );

        return $this;
    }

    /**
     * Assert that the Vue component's attribute at the given key has the given value.
     *
     * @param  string  $key
     * @param  string  $value
     * @param  string|null  $componentSelector
     * @return $this
     */
    public function assertVue($key, $value, $componentSelector = null)
    {
        PHPUnit::assertEquals($value, $this->vueAttribute($componentSelector, $key));

        return $this;
    }

    /**
     * Assert that the Vue component's attribute at the given key
     * does not have the given value.
     *
     * @param  string  $key
     * @param  string  $value
     * @param  string|null  $componentSelector
     * @return $this
     */
    public function assertVueIsNot($key, $value, $componentSelector = null)
    {
        PHPUnit::assertNotEquals($value, $this->vueAttribute($componentSelector, $key));

        return $this;
    }

    /**
     * Assert that the Vue component's attribute at the given key
     * is an array that contains the given value.
     *
     * @param  string  $key
     * @param  string  $value
     * @param  string|null  $componentSelector
     * @return $this
     */
    public function assertVueContains($key, $value, $componentSelector = null)
    {
        PHPUnit::assertTrue(
            Str::contains($this->vueAttribute($componentSelector, $key), $value)
        );

        return $this;
    }

    /**
     * Assert that the Vue component's attribute at the given key
     * is an array that does not contain the given value.
     *
     * @param  string  $key
     * @param  string  $value
     * @param  string|null  $componentSelector
     * @return $this
     */
    public function assertVueDoesNotContain($key, $value, $componentSelector = null)
    {
        PHPUnit::assertFalse(
            Str::contains($this->vueAttribute($componentSelector, $key), $value)
        );

        return $this;
    }

    /**
     * Retrieve the value of the Vue component's attribute at the given key.
     *
     * @param  string  $componentSelector
     * @param  string  $key
     * @return mixed
     */
    public function vueAttribute($componentSelector, $key)
    {
        $fullSelector = $this->resolver->format($componentSelector);

        return $this->driver->executeScript(
            "return document.querySelector('" . $fullSelector . "').__vue__." . $key
        );
    }
}

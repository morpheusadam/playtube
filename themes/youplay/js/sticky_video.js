(function (global) {
  'use strict'

  var StickyVideo = function (containerId) {
    this.containerId = containerId || 'sticky-container'
    this.container = document.getElementById(this.containerId)
    this.initialize()
  }

  StickyVideo.addClass = addClass
  StickyVideo.removeClass = removeClass
  StickyVideo.hasClass = hasClass
  StickyVideo.wrap = wrap
  StickyVideo.insertAfter = insertAfter

  StickyVideo.prototype.fixElementHeight = fixElementHeight
  StickyVideo.prototype.elementInViewport = elementInViewport
  StickyVideo.prototype.initialize = initialize

  function fixElementHeight () {
    this.container.parentElement.style.height = this.container.parentElement.offsetHeight + 'px'
  }
  function addClass (elements, className) {
    if (hasClass(elements, className)) return
    if (!elements) { return }
    if (typeof elements === 'string') {
      elements = document.querySelectorAll(elements)
    } else if (elements.tagName) { elements = [elements] }
    for (var i = 0; i < elements.length; i++) {
      if ((' ' + elements[i].className + ' ').indexOf(' ' + className + ' ') < 0) {
        elements[i].className += ' ' + className
      }
    }
  }
  function removeClass (elements, className) {
    if (!hasClass(elements, className)) return
    if (!elements) { return }
    if (typeof elements === 'string') {
      elements = document.querySelectorAll(elements)
    } else if (elements.tagName) { elements = [elements] }
    var reg = new RegExp('(^| )' + className + '($| )', 'g')
    for (var i = 0; i < elements.length; i++) {
      elements[i].className = elements[i].className.replace(reg, '')
    }
  }
  function hasClass (element, className) {
    return new RegExp('(\\s|^)' + className + '(\\s|$)').test(element.className)
  }
  function wrap (toWrap, wrapper) {
    return wrapper.appendChild(toWrap)
  }
  function insertAfter (el, referenceNode) {
    referenceNode.parentNode.insertBefore(el, referenceNode.nextSibling)
  }
  function elementInViewport (el) {
    var rect = el.getBoundingClientRect()
    return (rect.top > (el.offsetHeight * -1))
  }
  function initialize () {
    if (!this.container) return
    var that = this
    var wrapDiv = document.createElement('div')

    function onWindowScroll () {
      that.fixElementHeight()
      var parent = wrapDiv
      if (!that.elementInViewport(parent)) {
        StickyVideo.removeClass(parent, 'sticky-container_in-content')
        StickyVideo.addClass(parent, 'sticky-container_sticky')
      } else {
        StickyVideo.removeClass(parent, 'sticky-container_sticky')
        StickyVideo.addClass(parent, 'sticky-container_in-content')
      }
    }

    wrapDiv.className = 'sticky-container__wrap'
    this.container.parentElement.insertBefore(wrapDiv, this.container)
    StickyVideo.insertAfter(wrapDiv, that.container)
    StickyVideo.wrap(that.container, wrapDiv)
    StickyVideo.addClass(wrapDiv, 'sticky-container_in-content')
    StickyVideo.addClass(that.container, 'sticky-container__video')

    window.addEventListener ? window.addEventListener('scroll', onWindowScroll) : window.onscroll = onWindowScroll
  }

  // AMD support
  if (typeof define === 'function' && define.amd) {
    define(function () { return StickyVideo })
    // CommonJS and Node.js module support.
  } else if (typeof exports !== 'undefined') {
    // Support Node.js specific `module.exports` (which can be a function)
    if (typeof module !== 'undefined' && module.exports) {
      exports = module.exports = StickyVideo
    }
    // But always support CommonJS module 1.1.1 spec (`exports` cannot be a function)
    exports.StickyVideo = StickyVideo
  } else {
    global.StickyVideo = StickyVideo
  }
})(this)

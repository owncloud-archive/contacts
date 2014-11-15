/home/tobia/Workspace/chat-xmpp/app/apps/contacts/css//*
 * jQuery Combobox Plugin 1.1.2
 * Copyright 2011 Eugene Poltorakov (http://poltorakov.com) 
 * Licensed under the MIT License: http://www.opensource.org/licenses/mit-license.php
 * 
 * There are several event which triggered from this plugin on select element.
 * - update_position - Trigger before update position of dropdown. 
 *                     It gets calculated offset object as second argument which could be changed to affect position of dropdown;
 * - before_show - trigger before show dropdown;
 * - before_hide - trigger before hide dropdown;
 * - combo_init - trigger after init;
 * 
 * 
 * todo: 
 * - somehow handle mobile devices
 * - add adjustWidth & basic theme support overview in readme
 * - make multiselect widget using checkboxes
 * - remake tab action
 * - use event namespace
 * - mobile version ?
 */
(function($, undefined ) {
  var Combobox = function(element, options) {
    var self = this,
        $element = $(element),
        _hover_inited = false;
    
    if (element.combobox != undefined) {
      return false;
    }
    
    element.combobox = self;
    
    self.options = options;
    self.element = element;
    self.$element = $element;
    self.multiple = element.multiple;
    
    //create new elements and make link to the object on it
    (self._list = (self.list = $('<ul class="' + self.options.classes.list + (self.multiple ? ' ' + self.options.classes.multiple : '') + '" />')).get(0)).combobox = self;
    
    if (!self.multiple) {
      (self._wrapper = (self.wrapper = $('<div class="' + self.options.classes.wrapper + '" />')).get(0)).combobox = self;
      (self._selected = (self.selected = $('<div class="' + self.options.classes.selected + '" />')).get(0)).combobox = self;
      (self._button = (self.button = $('<div class="' + self.options.classes.button + '">+</div>')).get(0)).combobox = self;
    }
    
    //bind select events
    self.$element.bind('change', function(e) {
      self.updateSelected();
      // also check if select was disabled or enabled
      self.updateDisabled();
    }).bind('focus', function(e) {
      self.focus();
    }).bind('blur', function(e) {
      self.blur();
    }).bind('keypress', function(e){
      switch(e.which) {
        case 38:
        case 40:
          self.updateSelected();
          break;
      }
    });
    
    var _form = self.element.form;
    if (_form !== null && _form.combobox_reset_processed === undefined) {
      _form.combobox_reset_processed = true;
      $(_form).bind('reset', function(e) {
        setTimeout(function(){
          $.each(_form.elements, function() {
            if (this.tagName == 'SELECT' && typeof(this.combobox) != 'undefined') {
              this.combobox.updateSelected();
            }
          });
        }, 1);
      });
    }
    
    if (!self.multiple) {
      self.wrapper.bind('click', function(e) {
        if (self.isDisabled()) {
          return false;
        }
        if (self.blocked) {
          return false;
        }
        if (self.state) {
          self.hide();
        }
        else {
          if (!_hover_inited) {
            _hover_inited = true;
            //hide droplist when cursor is going outside
            self.list.add(self.button).add(self.selected).hover(function(e) {
              clearTimeout(self.timer);
            }, function(e) {
              self.timer = setTimeout(function() {
                self.hide();
              }, 400);
            });
          }
          self.show();
        }
        self.focus();
        return false;
      }).bind('keydown', function(e){
        var $options = self.$element.find('option'),
            index = $options.index($options.filter(':selected'));
        switch(e.which) {
          case 38:
            if (index > 0) {
              $options.eq(index - 1).prop('selected', 'selected');
              self.$element.change();
            }
            break;
          case 40:
            if (index < $options.length) {
              $options.eq(index + 1).prop('selected', 'selected');
              self.$element.change();
            }
            break;
        }
      }).bind('focus', function(e){//TODO
         self.focus();
      }).bind('blur', function(e){
         self.blur();
      });
    }
    else {
      self.list.bind('keydown', function(e){
        var $options = self.$element.find('option'),
            ctrl  = e.ctrlKey || e.metaKey,
            index;
        switch(e.which) {
          case 38:
            index = $options.index($options.filter(':selected').first());
            index = index == -1 ? 1 : index;
            index = (index > $options.length - 1) ? $options.length - 1 : index;
            if (!ctrl) {
              $options.prop('selected', null);
            }
            $options.eq(index - 1).prop('selected', 'selected');
            self.$element.change();
            break;
          case 40:
            index = $options.index($options.filter(':selected').last());
            index = index == -1 ? -1 : index;
            index = (index >= $options.length - 1) ? -1 : index;
            if (!ctrl) {
              $options.prop('selected', null);
            }
            $options.eq(index + 1).prop('selected', 'selected');
            self.$element.change();
            break;
        }
      });
    }
    
    if (self.options.hoverEnabled && !self.multiple) {
      self.wrapper.hover(function() {
        self.wrapper.addClass(self.options.classes.wrapHover);
      }, function() {
        //todo - check lines above - it could be problems here (class will not remove)
        if (!self.blocked && !self.state) {
          self.wrapper.removeClass(self.options.classes.wrapHover);
        }
      });
    }

    //collect select styles
    self.width  = (self.options.width  ? self.options.width  : self.$element.outerWidth());
    self.height = (self.options.height ? self.options.height : self.$element.outerHeight());
    self.btnWidth = (self.options.btnWidth);
    
    if (self.options.adjustWidth && self.btnWidth > 20) {
      switch(typeof(self.options.adjustWidth)) {
        case 'boolean':
          self.width = self.width + self.options.btnWidth - 20;
          break;
        case 'number':
          self.width = self.width + self.options.adjustWidth;
          break;
      }
    }
    
    if (!self.multiple) {
      self.wrapper.css({
        display: 'inline-block',
        width: self.width,
        height: self.height
      }).prop('tabindex', 0);
      self.button.css({
        width: self.btnWidth,
        height: self.height,
        display: 'inline-block'
      });
      self.selected.css({
        width: self.width - self.btnWidth,
        height: self.height,
        display: 'inline-block'
      });
      self.list.css({
        width: self.width,
        position: 'absolute'
      });
    }
    else {
      self._list.style.display = 'inline-block';
      self._list.style.width = self.width + 'px';
      self.list.prop('tabindex', 0);
    }
    
    if (!self.multiple) {
      self.wrapper.append(self.button);
      self.wrapper.append(self.selected);
      self.wrapper.insertAfter(self.$element);
    }
    else {
      self.list.insertAfter(self.$element);
    }
    
    //init
    self.updateDisabled();
    self.rebuild();
    self.updateSelected();
    
    if (!self.multiple) {
      self._list.style.display = 'none';
      $('body').append(self.list);
    }
    self.$element.hide().trigger('combo_init');
  };
  
  $.extend(Combobox.prototype, {
    state: false,
    blocked: false,
    timer: null,
    value: null,
    disabled: false,
    multiple: false,
    groups: false,
    filter: function(name, value) {
      return value;
    },
    default_options: {
      width: false,
      height: false,
      btnWidth: 15,
      showSpeed: 'fast',
      hideSpeed: 'fast',
      hideSelected: false,
      listMaxHeight: false,
      hoverEnabled: false,
      forceScroll: false,
      adjustWidth: true,
      theme: 'combo'
    },
    default_classes: {
      wrapper: 'wrapper',
      focus: 'focus',
      disabled: 'disabled',
      multiple: 'multiple',
      button: 'button',
      group: 'group',
      groupLabel: 'group-label',
      list: 'list',
      selected: 'selected',
      itemHover: 'item-hover',
      itemActive: 'item-active', 
      wrapHover: 'wrapper-hover',
      wrapActive: 'wrapper-active',
      listLong : 'list-long'
    },
    rebuild: function() {
      var self = this,
          elements = new Object,
          _html = '';
          
      self.groups = self.$element.find('optgroup:first').length == 0 ? false : true;
      
      if (self.groups) {
        self.$element.children().each(function(){
          switch(this.tagName) {
            case 'OPTION':
              elements[this.index] = {
                text: this.text,
                value: this.value
              };
              
              _html += '<li>'+ elements[this.index].text +'</li>';
              break;
            case 'OPTGROUP':
              var $optgroup = $(this),
                  $options = $optgroup.children('option');
              
              //if group have options
              if ($options.length > 0) {
                var label = $optgroup.prop('label');
                if (label != undefined) {
                  _html += '<li class="'+ self.options.classes.groupLabel +'">' + label + '</li>';
                }
                _html += '<li class="'+ self.options.classes.group +'"><ul>';
                $options.each(function() {
                  elements[this.index] = {
                    text: this.text,
                    value: this.value
                  };
                  
                  _html += '<li>'+ this.text +'</li>';
                });
                _html += '</ul></li>';
              }
              break;
          }
        });
      }
      else {
        self.$element.find('option').each(function() {
          elements[this.index] = {
            text: this.text,
            value: this.value
          };
          
          _html += '<li>'+ this.text +'</li>';
        });
      }
      self.list.html(_html);
      
      var $children = !self.groups ? self.list.find('li') : self.list.find('li').not('.' + self.options.classes.groupLabel).not('.' + self.options.classes.group);
      
      $children.each(function(index) {
        this.combobox_index = index;
        this.combobox_value = elements[index].value;
      });
      
      if (self.options.hoverEnabled) {
        self.list.mouseenter(function(e){
          if(this.mouseenter_inited === true) {
            return;
          }
          this.mouseenter_inited = true;
          
          $children.mouseover(function() {
            $(this).addClass(self.options.classes.itemHover);
          }).mouseout(function(){
            $(this).removeClass(self.options.classes.itemHover);
          });
        });
      }
      
      self.list.click(function(e) {
        var isCtrl = e.ctrlKey || e.metaKey;

        if (e.target.nodeName != 'LI') {
          return;
        }
        if (self.isDisabled()) {
          return false;
        }
        var children = self.$element.find('option');

        if (self.multiple && isCtrl) {
          if (children.get(e.target.combobox_index).selected == true) {
            children.get(e.target.combobox_index).selected = false;
          }
          else {
            children.get(e.target.combobox_index).selected = true;
          }
        }
        else if (self.multiple && e.shiftKey) {
          var $selected = children.filter(':selected'),
              firstIndex = children.index($selected.first()),
              index = e.target.combobox_index;

          firstIndex = firstIndex == -1 ? 0 : firstIndex;

          if (firstIndex > index) {
            index = firstIndex;
            firstIndex = e.target.combobox_index;
          }
          children.prop('selected', null).slice(firstIndex, index + 1).prop('selected', 'selected');
        }
        else if (self.multiple){
          children.filter(':selected').prop('selected', null);
          children.eq(e.target.combobox_index).prop('selected', 'selected');
        }
        else {
          children.eq(e.target.combobox_index).prop('selected', 'selected');
        }
        
        self.$element.change();
        self.updateSelected();
        if (!self.multiple) {
          self.hide();
        }
        self.removeSelection();
      });
      
      return self;
    },
    updateSelected: function() {
      var self = this,
        $children = !self.groups ? self.list.find('li') : self.list.find('li').not('.' + self.options.classes.groupLabel).not('.' + self.options.classes.group),
        $selected = self.$element.find('option').filter(':selected'),
        selectedText;

      //debugger;

      if (!self.multiple) {
        selectedText = $selected.text();
        self.selected.text(typeof self.options.filter == 'function' ? self.options.filter('selected', selectedText) : selectedText);
      }
      
      $children.filter('.' + self.options.classes.itemActive).removeClass(self.options.classes.itemActive);
      if ($selected.length == 0) {
        self.value = null;
      }
      else {
        var index = $selected.get(0).index;
        if (self.multiple) {
          self.value = [];
          $selected.each(function() {
            $children.eq(this.index).addClass(self.options.classes.itemActive);
            self.value[self.value.length] = this.value
          });
        }
        else {
          self.value = $selected.prop('value');
          $children.eq($selected.get(0).index).addClass(self.options.classes.itemActive);
        }
      }
      return self;
    },
    updatePosition: function() {
      var self = this;
      
      if (self.multiple) {
        return false;
      }
      
      var offset = self.wrapper.offset();
      
      offset = {
        top: offset.top + self.height,
        left: offset.left
      };
      
      self.$element.trigger('update_position', offset);
      
      self.list.css(offset);
      
      return self;
    },
    show: function() {
      var self = this;
      
      if (self.blocked) {
        return;
      }
      
      self.$element.trigger('before_show');
      
      if (self.options.hideSelected && !self.multiple) {
        $children = !self.groups ? self.list.find('li') : self.list.find('li').not('.' + self.options.classes.groupLabel).not('.' + self.options.classes.group),
        
        $children.show();
        
        for(i=0; i<$children.length; i++) {
          if ($children.get(i).combobox_value == self.value) {
            $children.eq(i).hide();
            break;
          }
        }
      }
      
      self.blocked = true;
      self.wrapper.addClass(self.options.classes.wrapActive);
      
      var shouldScrollTo = false;
      
      if (self.options.listMaxHeight) {
        self.list.css({
          top: '-9999px',
          left: '-9999px'
        });
        
        if (self.list.height() >= self.options.listMaxHeight) {
          self.list.addClass(this.options.classes.listLong);
          if (self.options.forceScroll) {
            self.list.css({
              height: self.options.listMaxHeight,
              overflow: 'auto'
            });
          }
          shouldScrollTo = true;
        }
        else {
          self.list.removeClass(self.options.classes.listLong);
        }
        
        self.list.hide();
        self.updatePosition();
        self.list.show();
      }
      else {
        this.updatePosition();
      }
      
      self.list.slideDown(self.options.showSpeed, function() {
        self.state = true;
        self.blocked = false;
        
        if (shouldScrollTo) {
          //scroll to the selected element
          var $item_active = self.list.find('.' + self.options.classes.itemActive);
          
          if ($item_active.length == 1) {
            if (self.options.hideSelected) {
              //try to find nearest element
              var $near = $item_active.prev();
              
              if ($near.length == 0) {
                $near = $item_active.next();
              }
              if ($near.length == 1) {
                self.list.scrollTop($near.get(0).offsetTop);
              }
            }
            else {
              self.list.scrollTop($item_active.get(0).offsetTop);
            }
          }
        }
      });
        
      return self;
    },
    hide: function() {
      var self = this;
      
      if (self.blocked || !self.state) {
        return false;
      }
      
      self.$element.trigger('before_hide');
      
      self.blocked = true;
      self.wrapper.removeClass(self.options.classes.wrapActive);
      
      if (self.options.hoverEnabled) {
        //todo - this class probably shouldn't remove here
        self.wrapper.removeClass(self.options.classes.wrapHover);
      }
      
      if (self.options.hideSpeed == 0) {
        self.list.hide();
        self.state = false;
        self.blocked = false;
      }
      else {
        self.list.slideUp(self.options.hideSpeed, function() {
          self.state = false;
          self.blocked = false;
        });
      }
      
      return self;
    },
    isDisabled: function() {
      var dValue = this.$element.prop('disabled');
      var roValue = this.$element.attr('readonly');
      return !!((dValue === 'disabled' || dValue == true) || (roValue === 'readonly' || roValue == true));
    },
    updateDisabled: function() {
      var self = this;
      
      self.disabled = self.isDisabled();
      
      var target = self.multiple ? self.list : self.wrapper;
      
      if (self.disabled) {
        target.addClass(self.options.classes.disabled);
      }
      else {
        target.removeClass(self.options.classes.disabled);
      }
      
      return self.disabled;
    },
    removeSelection: function() {
      if (window.getSelection) {
        window.getSelection().removeAllRanges();
      }
      else if (document.selection && document.selection.type != 'None') {
        //todo - error could appear here (on ie8+)
        try {document.selection.empty();} catch(e) {}
      }
    },
    focus: function() {
      if (this.wrapper !== undefined) {
        this.wrapper.addClass(this.options.classes.focus);
      }
      else {
        this.list.addClass(this.options.classes.focus);
      }
    },
    blur: function() {
      if (this.wrapper !== undefined) {
        this.wrapper.removeClass(this.options.classes.focus);
      }
      else {
        this.list.removeClass(this.options.classes.focus);
      }
    }
  });

  $.fn.combobox = function(options, classes) {
    options = $.extend({}, Combobox.prototype.default_options, options);
    options.classes = $.extend({}, Combobox.prototype.default_classes, classes);
    //prepare classes
    $.each(options.classes, function(i,v) {
      options.classes[i] = options.theme + '-' + v;
    });
    
    return this.each(function() {
      var c = new Combobox(this, options);
    });
  };
})(jQuery);

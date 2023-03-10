import Sortable from 'sortablejs';
import h from '../common/helpers';
import dom from '../common/dom';
import {getString} from '../common/utils';
import {data} from '../common/data';

const defaults = {
  type: 'field'
};

/**
 * Edit and control sliding panels
 */
export default class Panels {
  /**
 * Panels initial setup
 * @param  {Object} options Panels config
 * @return {Object} Panels
 */
  constructor(options) {
    const _this = this;
    _this.opts = Object.assign({}, defaults, options);

    _this.labels = _this.panelNav();
    const panels = _this.panelsWrap();

    _this.panels = panels.childNodes;
    _this.currentPanel = _this.panels[0];
    _this.nav = _this.navActions();
    if (_this.opts.type === 'field') {
      setTimeout(_this.setPanelsHeight.bind(_this), 100);
    }

    _this.panelDisplay = 'slider';

    return {
      content: [_this.labels, panels],
      nav: _this.nav,
      actions: {
        resize: _this.resizePanels.bind(_this)
      }
    };
  }

  /**
   * Resize the panel after its contents change in height
   * @return {String} panel's height in pixels
   */
  resizePanels() {
    const panelsWrap = this.panelsWrap;
    const column = panelsWrap.parentElement.parentElement;
    const width = parseInt(dom.getStyle(column, 'width'));
    const isTabbed = (width > 390) || this.opts.panels.length > 1;
    this.panelDisplay = isTabbed ? 'tabbed' : 'slider';
    if (isTabbed == true) {
      panelsWrap.parentElement.classList.add('tabbed-panels');
    } else {
      panelsWrap.parentElement.classList.remove('tabbed-panels');
    }
    const panelStyle = panelsWrap.style;
    const activePanelHeight = dom.getStyle(this.currentPanel, 'height');
    panelStyle.height = activePanelHeight;
    return activePanelHeight;
  }

  /**
   * Set panel height so we can animate it with css
   */
  setPanelsHeight() {
    const field = document.getElementById(this.opts.id);
    this.slideToggle = field.querySelector('.field-edit');

    // Temp styles
    this.slideToggle.style.display = 'block';
    this.slideToggle.style.position = 'absolute';
    this.slideToggle.style.opacity = 0;

    this.resizePanels();

    // Reset styles
    this.slideToggle.style.display = 'none';
    this.slideToggle.style.position = 'relative';
    this.slideToggle.style.opacity = 1;
    this.slideToggle.style.height = 'auto';
  }

  /**
   * Wrap a panel and make properties sortable
   * if the panel belongs to a field
   * @return {Object} DOM element
   */
  panelsWrap() {
    this.panelsWrap = dom.create({
      tag: 'div',
      attrs: {
        className: 'panels'
      },
      content: this.opts.panels
    });

    if (this.opts.type === 'field') {
      this.sortableProperties(this.panelsWrap);
    }

    return this.panelsWrap;
  }

  /**
   * Sortable panel properties
   * @param  {Array} panels
   * @return {Array} panel groups
   */
  sortableProperties(panels) {
    const _this = this;
    const groups = panels.getElementsByClassName('field-edit-group');

    return h.forEach(groups, (group, index) => {
      group.fieldID = _this.opts.id;
      if (group.isSortable) {
        Sortable.create(group, {
          animation: 150,
          group: {
            name: 'edit-' + group.editGroup,
            pull: true, put: ['properties']
          },
          sort: true,
          forceFallback: h.isFireFoxEdge(),
          fallbackOnBody: h.isFireFoxEdge(),
          fallbackClass: 'sortable-fallback-field',
          handle: '.prop-order',
          onSort: evt => {
            _this.propertySave(evt.to);
            _this.resizePanels();
          }
        });
      }
    });
  }

  /**
   * Save a fields' property
   * @param  {Object} group property group
   * @return {Object}       DOM node for updated property preview
   */
  propertySave(group) {
    const field = dom.fields.get(this.opts.id);
    data.save(group.editGroup, group, false);
    return field.instance.updatePreview();
  }

  /**
   * Panel navigation, tabs and arrow buttons for slider
   * @return {Object} DOM object for panel navigation wrapper
   */
  panelNav() {
    const _this = this;
    const panelNavLabels = {
      tag: 'div',
      attrs: {
        className: 'panel-labels'
      },
      content: {
        tag: 'div',
        content: []
      }
    };
    const panels = this.opts.panels; // Make new array

    for (let i = 0; i < panels.length; i++) {
      const panelLabel = {
        tag: 'h5',
        action: {
          click: evt => {
            const index = h.indexOfNode(evt.target, evt.target.parentElement);
            _this.currentPanel = _this.panels[index];
            const labels = evt.target.parentElement.childNodes;
            _this.nav.refresh(index);
            dom.removeClasses(labels, 'active-tab');
            evt.target.classList.add('active-tab');
          }
        },
        content: panels[i].config.label
      };
      delete panels[i].config.label;

      if (i === 0) {
        panelLabel.className = 'active-tab';
      }

      panelNavLabels.content.content.push(panelLabel);
    }

    const next = {
      tag: 'button',
      attrs: {
        className: 'next-group',
        title: getString('controlGroups.nextGroup'),
        type: 'button'
      },
      dataset: {
        toggle: 'tooltip',
        placement: 'top'
      },
      action: {
        click: (e) => this.nav.nextGroup(e)
      },
      content: dom.icon('triangle-right')
    };
    const prev = {
      tag: 'button',
      attrs: {
        className: 'prev-group',
        title: getString('controlGroups.prevGroup'),
        type: 'button'
      },
      dataset: {
        toggle: 'tooltip',
        placement: 'top'
      },
      action: {
        click: (e) => this.nav.prevGroup(e)
      },
      content: dom.icon('triangle-left')
    };

    return dom.create({
      tag: 'nav',
      attrs: {
        className: 'panel-nav'
      },
      content: [prev, panelNavLabels, next]
    });
  }

  /**
   * Handlers for navigating between panel groups
   * @todo refactor to use requestAnimationFrame instead of css transitions
   * @return {Object} actions that control panel groups
   */
  navActions() {
    const _this = this;
    const action = {};
    const groupParent = this.currentPanel.parentElement;
    const firstControlNav = this.labels.querySelector('.panel-labels').firstChild;
    const siblingGroups = this.currentPanel.parentElement.childNodes;
    let index = h.indexOfNode(this.currentPanel, groupParent);
    let offset = {};

    const groupChange = newIndex => {
      const labels = firstControlNav.childNodes;
      dom.removeClasses(labels, 'active-tab');
      firstControlNav.childNodes[newIndex].classList.add('active-tab');
      this.currentPanel = siblingGroups[newIndex];
      this.panelsWrap.style.height = dom.getStyle(this.currentPanel, 'height');
      if (this.opts.type === 'field') {
        this.slideToggle.style.height = 'auto';
      }
      return this.currentPanel;
    };

    const translateX = offset => {
      if (_this.panelDisplay !== 'tabbed') {
        firstControlNav.style.transform = `translateX(-${offset.nav}px)`;
      } else {
        firstControlNav.removeAttribute('style');
      }
      groupParent.style.transform = `translateX(-${offset.panel}px)`;
    };

    action.refresh = newIndex => {
      if (newIndex !== undefined) {
        index = newIndex;
        groupChange(newIndex);
      }
      _this.resizePanels();
      offset = {
        nav: firstControlNav.offsetWidth * index,
        panel: groupParent.offsetWidth * index
      };
      translateX(offset);
    };

    /**
     * Slides panel to the next group
     * @return {Object} current group after navigation
     */
    action.nextGroup = () => {
      const newIndex = index + 1;
      if (newIndex !== siblingGroups.length) {
        offset = {
          nav: firstControlNav.offsetWidth * newIndex,
          panel: groupParent.offsetWidth * newIndex
        };
        translateX(offset);
        groupChange(newIndex);
        index++;
      } else {
        const origOffset = {
          nav: firstControlNav.style.transform,
          panel: groupParent.style.transform
        };
        offset = {
          nav: (firstControlNav.offsetWidth * index) + 10,
          panel: (groupParent.offsetWidth * index) + 10
        };
        translateX(offset);
        setTimeout(() => {
          firstControlNav.style.transform = origOffset.nav;
          groupParent.style.transform = origOffset.panel;
        }, 150);
      }

      return this.currentPanel;
    };

    action.prevGroup = () => {
      if (index !== 0) {
        const newIndex = (index - 1);
        offset = {
          nav: firstControlNav.offsetWidth * newIndex,
          panel: groupParent.offsetWidth * newIndex
        };
        translateX(offset);
        groupChange(newIndex);
        index--;
      } else {
        const curTranslate = [
          firstControlNav.style.transform,
          groupParent.style.transform
        ];
        const nudge = 'translateX(10px)';
        firstControlNav.style.transform = nudge;
        groupParent.style.transform = nudge;
        setTimeout(() => {
          firstControlNav.style.transform = curTranslate[0];
          groupParent.style.transform = curTranslate[1];
        }, 150);
      }
    };

    return action;
  }
}

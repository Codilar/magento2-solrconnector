define([
    'jquery',
    'underscore'
], function ($, _) {

    var _CACHE = {};
    var sections = [];

    var validateCache = function (key) {
        if (!_CACHE.hasOwnProperty(key)) {
            return false;
        }
        for (var i = 0; i < sections.length; i++) {
            if (!_CACHE[key][sections[i]]) {
                console.log(sections, sections[i]);
                return false;
            }
        }
        return true;
    };

    var getCache = function (key) {
        return validateCache(key) ? _CACHE[key] : null;
    };

    var setCache = function (key, value, section) {
        var cache = _CACHE[key];
        if (!cache) {
            cache = {};
        }
        cache[section] = value;
        _CACHE[key] = cache;
    };

    var searchWidget = {
        options: {
            config: {
                currency_code: "",
                searchable_attributes: ['*'],
                useUserDefinedTemplate: false,
                template: "Codilar_SolrConnector/template/autocomplete.html",
                dataChangedEvent: "_codilar_solrconnector_autocomplete_data_changed",
                sections: {}
            },
            autocompleteContainerSelector: ".solr_search_autocomplete_container"
        },
        _template: null,
        _create: function () {

            $(document).on('click', function (event) {
                if (
                    !this._areElementsInclusive(this.element, $(event.target)) &&
                    !this._areElementsInclusive(this.getAutocompleteContainer(), $(event.target))
                ) {
                    this.getAutocompleteContainer().hide();
                } else {
                    this.getAutocompleteContainer().show();
                }
            }.bind(this));
            this.element.on('keyup', this.handleKeypress.bind(this));
            sections = Object.keys(this.options.config.sections);
            this._initTemplate();
        },
        _areElementsInclusive: function (parent, child) {
            return parent.is(child) || (parent.has(child).length > 0);
        },
        _buildQuery: function (query, section) {
            section = this.options.config.sections[section];
            var url = section.url;
            var searchQuery = [];
            section.searchable_attributes.forEach(function (attribute) {
                searchQuery.push(attribute+":*"+query+"*");
            });
            searchQuery = searchQuery.join(" OR ");
            return url+"?q="+searchQuery+"&rows="+section.count;
        },
        _initTemplate: function () {
            if (this.options.config.useUserDefinedTemplate) {
                this._template = _.template(this.options.config.template);
            } else  {
                require(["text!"+require.toUrl(this.options.config.template)], function (template) {
                    this._template = _.template(template);
                }.bind(this));
            }
        },
        getTemplate: function (data) {
            return this._template ? this._template(data) : "";
        },
        handleKeypress: function (event) {
            var self = this;
            var text = self.element.val();
            if (text.length) {
                self.search(text);
            } else {
                self.triggerDataChanged(null);
            }
        },
        _fetchSection: function (query, section) {
            var self = this;
            var templateData = getCache(query);
            $.ajax({
                url: self._buildQuery(query, section),
                method: "GET",
                success: function (result) {
                    if (result.responseHeader.status === 0) {
                        var data = [];
                        try {
                            data = result.response.docs;
                            setCache(query, data, section);
                            if (getCache(query)) {
                                self.triggerDataChanged(getCache(query));
                            }
                        } catch (e) {
                        }
                    }
                },
                error: function (error) {
                    self.triggerDataChanged(null);
                }
            });
        },
        search: function (query) {
            var self = this;
            if (getCache(query)) {
                self.triggerDataChanged(getCache(query));
            } else {
                sections.forEach(function (section) {
                    self._fetchSection(query, section);
                });
            }
        },
        getAutocompleteContainer: function () {
            return $(this.options.autocompleteContainerSelector);
        },
        triggerDataChanged: function (data) {
            if (data === null) { // if null set all section data to null
                data = {};
                sections.forEach(function (section) {
                    data[section] = null;
                });
            }
            data.currency_code = this.options.config.currency_code;
            data.i18n = $.mage.__;
            data.sections = sections;
            this.getAutocompleteContainer().trigger(this.options.config.dataChangedEvent, [data]);
            this.getAutocompleteContainer().html(this.getTemplate(data));
        }
    };

    return $.widget("solr.solrAutocomplete", searchWidget);
    
});
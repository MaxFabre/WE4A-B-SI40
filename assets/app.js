/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './stimulus_bootstrap.js';
import './styles/app.css';
import $ from 'jquery';

window.$ = $;
window.jQuery = $;

import TomSelect from "tom-select";

$(document).ready(function () {
    $('[film-tom-select]').each(function () {
        if (this.tomselect) return;

        new TomSelect(this, {
            valueField: 'id',
            labelField: 'title',
            searchField: 'title',

            maxOptions: 10,
            create: false,
            placeholder: 'Rechercher un film...',

            load: function(query, callback) {
                if (!query.length) return callback();

                $.ajax({
                    url: '/api/films/search',
                    type: 'GET',
                    data: {
                        q: query
                    },
                    success: function(results) {
                        callback(results);
                    },
                    error: function() {
                        callback();
                    }
                });
            }
        });
    });

    $('[personality-tom-select]').each(function () {
        if (this.tomselect) return;

        new TomSelect(this, {
            valueField: 'id',
            labelField: 'name',
            searchField: 'name',

            maxOptions: 10,
            create: false,
            placeholder: 'Rechercher une personnalité...',

            load: function(query, callback) {
                if (!query.length) return callback();

                $.ajax({
                    url: '/api/personalities/search',
                    type: 'GET',
                    data: {
                        q: query
                    },
                    success: function(results) {
                        callback(results);
                    },
                    error: function() {
                        callback();
                    }
                });
            }
        });
    });
});

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

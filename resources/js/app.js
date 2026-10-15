import Alpine from 'alpinejs';
import './category-manager';
import { initializeAutomaticFilters } from './filter-cache';

window.Alpine = Alpine;

Alpine.start();

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeAutomaticFilters);
else initializeAutomaticFilters();

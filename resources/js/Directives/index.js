import tooltipDirective from "./Tooltip.js";
import clickOutsideDirective from "./ClickOutside.js";
import selectorDirective from "./Selector.js";

// register all directives
const directives = (app) => {
    tooltipDirective(app);
    clickOutsideDirective(app);
    selectorDirective(app);
};

export default directives;

'use strict';

import YITH_WooCompare from './includes/YITH_WooCompare';
import {$window} from "./includes/globals";

( () => $window.yithCompare = new YITH_WooCompare() )();
/**
 *						AjaxCore 1.2.2
 *				http://ajaxcore.sourceforge.net/
 *
 *  AjaxCore is a PHP framework that aims the ease development of rich 
 *  AJAX applications, using Prototype's JavaScript standard library.
 *  
 *  Copyright 2007 Mauro Niewolski (niewolski@users.sourceforge.net)
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */
function AjaxCore(url,method,pars,response)
	{
		var GridRequest = new Ajax.Request(
			url, 
			{
				method: method, 
				parameters: pars, 
				onComplete: response
			});
	}
	
function AjaxCoreTimer(handle, ms)
{
    this.handle = handle;
    this.ms = ms;
    this.timer = 0;
}

AjaxCoreTimer.prototype.start = function()
{
    if (this.timer > 0)
        this.reset();
    this.timer = window.setTimeout(this.handle, this.ms);
}

AjaxCoreTimer.prototype.reset = function()
{
    if (this.timer > 0)
        window.clearTimeout(this.timer);
    this.timer = 0;
}

	

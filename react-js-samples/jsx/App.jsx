/* jshint esnext: true */
/* globals $, unicornWhisperer */
import React from 'react';
import StaticSingleDayOptInApp from './StaticSingleDayOptInApp';
import SvgSprite from 'promo-template-common/lib/components/SvgSprite';
import PromoActions from 'promo-template-common/lib/actions/PromoActions';
import Utility from 'promo-template-common/lib/utils/Utility';

// Wait until whisperer returns member ID before rendering App
unicornWhisperer
	.fetchData()
	.then(function (promotionData) {

		// if UnicornWhisperer doesn't give us memberID, check querystring var
		if (promotionData.member.id === undefined) {
			PromoActions.setMemberId(Utility.getURLParameters().m);
			// otherwise get it from reponse
		} else {
			PromoActions.setMemberId(promotionData.member.id);
		}

		React.render(
			(<div>
				<SvgSprite />
				<StaticSingleDayOptInApp />
			</div>), document.body);
	});

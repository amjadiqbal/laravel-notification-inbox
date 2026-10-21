<style>
    /* Phone frame shared by push and SMS. Neutral and cool tones only. */
    .pv-phone { width: 300px; border-radius: 42px; padding: 12px; background: #0F172A; box-shadow: 0 18px 40px rgba(15, 23, 42, .28); flex: none; }
    .pv-screen { border-radius: 32px; overflow: hidden; min-height: 520px; position: relative; display: flex; flex-direction: column; }
    .pv-notch { position: absolute; top: 9px; left: 50%; width: 90px; height: 24px; margin-left: -45px; border-radius: 14px; background: #0F172A; z-index: 2; }
    .pv-statusbar { display: flex; justify-content: space-between; padding: 12px 24px 0; font-size: 12px; font-weight: 600; }

    .pv-lock { background: linear-gradient(160deg, #312E81 0%, #4C1D95 45%, #0E7490 100%); color: #FFFFFF; }
    .pv-clock { text-align: center; margin-top: 34px; }
    .pv-clock b { display: block; font-size: 62px; font-weight: 300; line-height: 1; letter-spacing: -1px; }
    .pv-clock span { font-size: 14px; opacity: .85; }
    .pv-alert { margin: 26px 10px 0; padding: 11px 13px; border-radius: 18px; background: rgba(248, 250, 252, .9); color: #0F172A; box-shadow: 0 2px 10px rgba(15, 23, 42, .15); }
    .pv-alert-top { display: flex; align-items: center; gap: 7px; font-size: 12px; color: #475569; }
    .pv-app { width: 20px; height: 20px; border-radius: 5px; background: #7C3AED; color: #FFFFFF; font-size: 11px; font-weight: 700; display: grid; place-items: center; overflow: hidden; flex: none; }
    .pv-app img { width: 100%; height: 100%; object-fit: cover; }
    .pv-alert-top .pv-when { margin-left: auto; }
    .pv-alert h4 { margin: 6px 0 0; font-size: 14px; line-height: 1.3; }
    .pv-alert h5 { margin: 1px 0 0; font-size: 13px; font-weight: 600; color: #334155; }
    .pv-alert p { margin: 2px 0 0; font-size: 13.5px; line-height: 1.35; white-space: pre-line; word-break: break-word; }
    .pv-alert-image { margin-top: 8px; width: 100%; max-height: 160px; object-fit: cover; border-radius: 10px; display: block; }
    .pv-alert-actions { display: flex; gap: 6px; margin-top: 9px; }
    .pv-alert-actions span { flex: 1; text-align: center; padding: 6px; border-radius: 10px; background: rgba(15, 23, 42, .07); font-size: 12.5px; font-weight: 600; }
    .pv-badge { position: absolute; right: 18px; bottom: 26px; display: flex; align-items: center; gap: 8px; font-size: 12px; }
    .pv-badge i { min-width: 22px; height: 22px; padding: 0 6px; border-radius: 11px; background: #DC2626; color: #FFFFFF; font-style: normal; font-weight: 700; display: grid; place-items: center; }
    .pv-badge .pv-icon { width: 46px; height: 46px; border-radius: 12px; background: #7C3AED; position: relative; }
    .pv-badge .pv-icon i { position: absolute; top: -7px; right: -7px; }

    .pv-sms { background: #FFFFFF; color: #0F172A; }
    .pv-sms-head { text-align: center; padding: 10px 12px 10px; border-bottom: 1px solid #E5E7EB; background: #F8FAFC; }
    .pv-avatar { width: 44px; height: 44px; margin: 4px auto 4px; border-radius: 50%; background: #94A3B8; color: #FFFFFF; display: grid; place-items: center; font-weight: 700; }
    .pv-sms-head small { color: #64748B; }
    .pv-thread { flex: 1; padding: 14px 12px; display: flex; flex-direction: column; gap: 4px; }
    .pv-thread .pv-day { text-align: center; color: #94A3B8; font-size: 11px; margin-bottom: 6px; }
    .pv-bubble { align-self: flex-start; max-width: 82%; padding: 8px 12px; border-radius: 18px; border-bottom-left-radius: 6px; background: #E9E9EB; font-size: 14px; line-height: 1.35; white-space: pre-wrap; word-break: break-word; }
    .pv-bubble a { color: #2563EB; }
    .pv-bubble img { display: block; max-width: 100%; border-radius: 12px; margin-bottom: 6px; }
    .pv-facts { display: flex; flex-wrap: wrap; justify-content: center; gap: 6px 14px; margin-top: 12px; color: var(--ni-muted); font-size: 12.5px; }
    .pv-facts b { color: var(--ni-text); }
    .pv-warn { color: var(--ni-danger); font-weight: 600; }

    /* Slack */
    .pv-slack { width: 100%; max-width: 640px; background: #FFFFFF; color: #1D1C1D; border-radius: 10px; border: 1px solid #DDDDDD; padding: 14px 18px; font: 15px/1.47 -apple-system, "Segoe UI", Lato, Helvetica, Arial, sans-serif; }
    .pv-slack-msg { display: grid; grid-template-columns: 36px 1fr; gap: 0 9px; }
    .pv-slack-avatar { width: 36px; height: 36px; border-radius: 6px; background: #611F69; color: #FFFFFF; display: grid; place-items: center; font-weight: 700; }
    .pv-slack-name { font-weight: 900; }
    .pv-slack-tag { font-size: 10px; font-weight: 700; color: #616061; background: #EEEEEE; border-radius: 3px; padding: 1px 4px; margin-left: 4px; vertical-align: 2px; }
    .pv-slack-time { color: #616061; font-size: 12px; margin-left: 6px; }
    .pv-slack h3 { font-size: 18px; font-weight: 900; margin: 6px 0 4px; }
    .pv-slack-section { display: flex; gap: 12px; margin: 4px 0; }
    .pv-slack-section > div { flex: 1; min-width: 0; }
    .pv-slack-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 16px; margin-top: 6px; }
    .pv-slack-context { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; color: #616061; font-size: 13px; margin: 4px 0; }
    .pv-slack-context img { width: 18px; height: 18px; border-radius: 3px; }
    .pv-slack hr { border: 0; border-top: 1px solid #DDDDDD; margin: 10px 0; }
    .pv-slack-buttons { display: flex; flex-wrap: wrap; gap: 8px; margin: 8px 0 4px; }
    .pv-slack-buttons span { padding: 3px 12px; border-radius: 4px; border: 1px solid #BBBBBB; font-size: 14px; font-weight: 700; }
    .pv-slack-buttons .primary { background: #007A5A; border-color: #007A5A; color: #FFFFFF; }
    .pv-slack-buttons .danger { color: #E01E5A; border-color: #E01E5A; }
    .pv-slack-img { max-width: 360px; max-height: 260px; border-radius: 8px; display: block; margin: 6px 0; }
    .pv-slack-accessory { width: 88px; height: 88px; object-fit: cover; border-radius: 6px; }
    .pv-slack-att { border-left: 4px solid #DDDDDD; padding: 2px 0 2px 12px; margin: 6px 0; }
    .pv-slack-att a { color: #1264A3; font-weight: 700; text-decoration: none; }
    .pv-slack-foot { color: #616061; font-size: 12px; }
    .pv-slack code { background: #F6F6F6; border: 1px solid #DDDDDD; border-radius: 3px; padding: 0 3px; color: #E01E5A; font-size: 12px; }
    .pv-slack pre { background: #F8F8F8; border: 1px solid #DDDDDD; border-radius: 4px; padding: 8px; white-space: pre-wrap; font-size: 12px; }
    .pv-slack a { color: #1264A3; }
    .ni-mention { background: #E8F5FA; color: #1264A3; border-radius: 3px; padding: 0 2px; }

    /* Microsoft Teams */
    .pv-teams { width: 100%; max-width: 560px; background: #F5F5F5; border-radius: 8px; padding: 14px; font: 14px/1.43 "Segoe UI", -apple-system, Helvetica, Arial, sans-serif; color: #242424; }
    .pv-teams-head { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 12px; color: #616161; }
    .pv-teams-head b { color: #242424; }
    .pv-teams-avatar { width: 32px; height: 32px; border-radius: 50%; background: #5B5FC7; color: #FFFFFF; display: grid; place-items: center; font-weight: 700; }
    .pv-teams-card { background: #FFFFFF; border-radius: 6px; border: 1px solid #E0E0E0; padding: 14px 16px; }
    .pv-teams-card .t-large { font-size: 18px; } .pv-teams-card .t-extraLarge { font-size: 22px; } .pv-teams-card .t-medium { font-size: 15px; } .pv-teams-card .t-small { font-size: 12px; }
    .pv-teams-card .t-bolder { font-weight: 700; } .pv-teams-card .t-subtle { color: #616161; }
    .pv-teams-card .c-good { color: #0E7A0B; } .pv-teams-card .c-attention { color: #C4314B; } .pv-teams-card .c-accent { color: #5B5FC7; } .pv-teams-card .c-warning { color: #CA8A04; }
    .pv-teams-block { margin: 6px 0; word-break: break-word; }
    .pv-teams-block.sep { border-top: 1px solid #E0E0E0; padding-top: 8px; }
    .pv-teams-facts { display: grid; grid-template-columns: auto 1fr; gap: 4px 14px; }
    .pv-teams-facts dt { font-weight: 700; } .pv-teams-facts dd { margin: 0; }
    .pv-teams-cols { display: flex; gap: 12px; } .pv-teams-cols > div { flex: 1; min-width: 0; }
    .pv-teams img { max-width: 100%; border-radius: 4px; }
    .pv-teams-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
    .pv-teams-actions span { flex: 1; min-width: 120px; text-align: center; padding: 6px 12px; border: 1px solid #D1D1D1; border-radius: 4px; background: #FFFFFF; font-weight: 600; }
    .pv-teams-actions .positive { background: #5B5FC7; border-color: #5B5FC7; color: #FFFFFF; }
    .pv-teams a { color: #5B5FC7; }

    /* Telegram */
    .pv-tg { width: 100%; max-width: 440px; border-radius: 12px; overflow: hidden; font: 15px/1.4 -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
    .pv-tg-head { display: flex; align-items: center; gap: 10px; padding: 9px 14px; background: #FFFFFF; color: #000000; border-bottom: 1px solid #E5E7EB; }
    .pv-tg-avatar { width: 38px; height: 38px; border-radius: 50%; background: #229ED9; color: #FFFFFF; display: grid; place-items: center; font-weight: 700; }
    .pv-tg-head small { display: block; color: #707579; font-size: 13px; }
    .pv-tg-body { padding: 16px 12px 18px; background: #C9DCEB; background-image: radial-gradient(rgba(255, 255, 255, .35) 1px, transparent 1px); background-size: 14px 14px; }
    .pv-tg-bubble { max-width: 88%; background: #FFFFFF; color: #000000; border-radius: 14px; border-bottom-left-radius: 4px; padding: 7px 10px 6px; box-shadow: 0 1px 1px rgba(15, 23, 42, .12); word-break: break-word; }
    .pv-tg-bubble img { display: block; max-width: 100%; border-radius: 10px; margin: -2px -4px 6px; width: calc(100% + 8px); }
    .pv-tg-file { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
    .pv-tg-file i { width: 40px; height: 40px; border-radius: 50%; background: #229ED9; display: inline-block; flex: none; }
    .pv-tg-time { text-align: right; color: #8C9AA6; font-size: 11px; margin-top: 2px; }
    .pv-tg-bubble a { color: #168ACD; }
    .pv-tg-bubble code { color: #2E7D32; font-size: 13px; }
    .pv-tg-bubble pre { background: #F4F4F5; padding: 6px 8px; border-radius: 6px; white-space: pre-wrap; font-size: 13px; }
    .pv-tg-keys { max-width: 88%; display: grid; gap: 4px; margin-top: 4px; }
    .pv-tg-keys div { display: flex; gap: 4px; }
    .pv-tg-keys span { flex: 1; text-align: center; padding: 8px; border-radius: 10px; background: rgba(51, 65, 85, .42); color: #FFFFFF; font-size: 14px; font-weight: 600; }
    .pv-tg-keys span.link::after { content: " ↗"; font-size: 11px; opacity: .8; }
    .ni-spoiler { background: #94A3B8; color: transparent; border-radius: 3px; }

    /* Discord */
    .pv-dc { width: 100%; max-width: 620px; background: #313338; color: #DBDEE1; border-radius: 8px; padding: 14px 16px; font: 15px/1.375 "gg sans", "Noto Sans", Helvetica, Arial, sans-serif; }
    .pv-dc-msg { display: grid; grid-template-columns: 40px 1fr; gap: 0 14px; }
    .pv-dc-avatar { width: 40px; height: 40px; border-radius: 50%; background: #5865F2; color: #FFFFFF; display: grid; place-items: center; font-weight: 700; }
    .pv-dc-name { color: #F2F3F5; font-weight: 600; }
    .pv-dc-tag { background: #5865F2; color: #FFFFFF; font-size: 10px; font-weight: 700; border-radius: 3px; padding: 1px 4px; margin-left: 4px; vertical-align: 2px; }
    .pv-dc-time { color: #949BA4; font-size: 12px; margin-left: 6px; }
    .pv-dc-embed { margin-top: 6px; max-width: 520px; background: #2B2D31; border-left: 4px solid #1E1F22; border-radius: 4px; padding: 10px 14px 12px; display: grid; grid-template-columns: 1fr auto; gap: 8px; }
    .pv-dc-embed .pv-dc-title { color: #F2F3F5; font-weight: 600; }
    .pv-dc-embed a.pv-dc-title { color: #00A8FC; text-decoration: none; }
    .pv-dc-fields { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 8px; }
    .pv-dc-fields div { grid-column: span 3; } .pv-dc-fields div.inline { grid-column: span 1; }
    .pv-dc-fields b { display: block; color: #F2F3F5; font-size: 14px; }
    .pv-dc-thumb { width: 80px; height: 80px; object-fit: cover; border-radius: 4px; }
    .pv-dc-image { max-width: 100%; border-radius: 4px; margin-top: 10px; display: block; }
    .pv-dc-foot { color: #949BA4; font-size: 12px; margin-top: 8px; }
    .pv-dc-buttons { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
    .pv-dc-buttons span { padding: 4px 14px; border-radius: 4px; background: #4E5058; color: #FFFFFF; font-size: 14px; font-weight: 500; }
    .pv-dc a { color: #00A8FC; }
    .pv-dc code { background: #2B2D31; border-radius: 3px; padding: 0 3px; font-size: 13px; }
    .pv-dc pre { background: #2B2D31; border: 1px solid #1E1F22; border-radius: 4px; padding: 8px; white-space: pre-wrap; font-size: 13px; }

    /* Broadcast */
    .pv-bc { width: 100%; max-width: 640px; }
    .pv-bc-row { display: grid; grid-template-columns: 110px 1fr; gap: 8px 14px; margin-bottom: 12px; align-items: start; }
    .pv-bc-row > span:first-child { color: var(--ni-muted); font-weight: 600; font-size: 13px; padding-top: 3px; }
    .pv-bc-pill { display: inline-block; margin: 0 6px 6px 0; padding: 3px 10px; border-radius: 999px; background: var(--ni-panel); border: 1px solid var(--ni-border-strong); font-size: 12.5px; }
</style>

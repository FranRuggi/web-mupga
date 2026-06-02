<section class="main-new__right main-rightbar" id="account-section">
    <div class="main-rightbar__scroll">

        <?php if(isset($srvInfo) && is_array($srvInfo)) : ?>
        <div class="main-rightbar__server-statistics server-statistics statistics">
            <span class="server-statistics__header rightbar__title statistics__title">
                <span><?php echo lang('module_titles_txt_17'); ?></span>
            </span>

            <ul class="server-statistics__list server-statistics__list--show statistics__card list-reset" id="stat1">
                <?php if(check_value(config('server_info_season', true))): ?>
                    <li class="server-statistics__item statistics__item">
                        <?php echo lang('sidebar_srvinfo_txt_6'); ?>:
                        <span class="selection-text--light-blue">
                            <?php echo htmlspecialchars(config('server_info_season', true)); ?>
                        </span>
                    </li>
                <?php endif; ?>

                <?php if(check_value(config('server_info_exp', true))): ?>
                    <li class="server-statistics__item statistics__item">
                        <?php echo lang('sidebar_srvinfo_txt_7'); ?>:
                        <span class="selection-text--light-blue">
                            <?php echo htmlspecialchars(config('server_info_exp', true)); ?>
                        </span>
                    </li>
                <?php endif; ?>

                <?php if(check_value(config('server_info_masterexp', true))): ?>
                    <li class="server-statistics__item statistics__item">
                        <?php echo lang('sidebar_srvinfo_txt_8'); ?>:
                        <span class="selection-text--light-blue">
                            <?php echo htmlspecialchars(config('server_info_masterexp', true)); ?>
                        </span>
                    </li>
                <?php endif; ?>

                <?php if(check_value(config('server_info_drop', true))): ?>
                    <li class="server-statistics__item statistics__item">
                        <?php echo lang('sidebar_srvinfo_txt_9'); ?>:
                        <span class="selection-text--light-blue">
                            <?php echo htmlspecialchars(config('server_info_drop', true)); ?>
                        </span>
                    </li>
                <?php endif; ?>

                <li class="server-statistics__item statistics__item">
                    <?php echo lang('sidebar_srvinfo_txt_2'); ?>:
                    <span class="selection-text--light-blue">
                        <?php echo number_format($srvInfo[0]); ?>
                    </span>
                </li>

                <li class="server-statistics__item statistics__item">
                    <?php echo lang('sidebar_srvinfo_txt_3'); ?>:
                    <span class="selection-text--light-blue">
                        <?php echo number_format($srvInfo[1]); ?>
                    </span>
                </li>

                <li class="server-statistics__item statistics__item">
                    <?php echo lang('sidebar_srvinfo_txt_4'); ?>:
                    <span class="selection-text--light-blue">
                        <?php echo number_format($srvInfo[2]); ?>
                    </span>
                </li>

                <?php if(check_value(config('maximum_online', true))): ?>
                    <li class="server-statistics__item statistics__item">
                        <?php echo lang('sidebar_srvinfo_txt_5'); ?>:
                        <span class="selection-text--light-blue">
                            <?php echo number_format($onlinePlayers); ?>
                        </span>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php
        $classData = templateClassInfo();
        if(is_array($classData) && count($classData) > 0) {
            $DarkWizardTotal = $DarkKnightTotal = $ElfTotal = $MagicGladiatorTotal = $DarkLordTotal = $SummonerTotal = $RageFighterTotal = 0;
            foreach($classData as $row) {
                $c = isset($row[_CLMN_CHR_CLASS_]) ? $row[_CLMN_CHR_CLASS_] : (isset($row['Class']) ? $row['Class'] : null);
                if($c === null) continue;
                switch((int)$c) {
                    case 0: case 1: case 2: $DarkWizardTotal++; break;
                    case 16: case 17: case 18: $DarkKnightTotal++; break;
                    case 32: case 33: case 34: $ElfTotal++; break;
                    case 48: case 50: $MagicGladiatorTotal++; break;
                    case 64: case 66: $DarkLordTotal++; break;
                    case 80: case 81: case 82: $SummonerTotal++; break;
                    case 96: case 98: $RageFighterTotal++; break;
                }
            }
            $ClassTotal = $DarkWizardTotal + $DarkKnightTotal + $ElfTotal + $MagicGladiatorTotal + $DarkLordTotal + $SummonerTotal + $RageFighterTotal;
            if($ClassTotal > 0) {
                $DarkWizardPercent = round(($DarkWizardTotal / $ClassTotal) * 100);
                $DarkKnightPercent = round(($DarkKnightTotal / $ClassTotal) * 100);
                $ElfPercent = round(($ElfTotal / $ClassTotal) * 100);
                $MagicGladiatorPercent = round(($MagicGladiatorTotal / $ClassTotal) * 100);
                $DarkLordPercent = round(($DarkLordTotal / $ClassTotal) * 100);
                $SummonerPercent = round(($SummonerTotal / $ClassTotal) * 100);
                $RageFighterPercent = round(($RageFighterTotal / $ClassTotal) * 100);
        ?>
        <div class="main-rightbar__server-statistics server-statistics statistics class-percent-widget">
            <span class="server-statistics__header rightbar__title statistics__title">
                <span><?php echo lang('rankings_txt_11', true) ?: 'Class'; ?> %</span>
            </span>
            <ul class="server-statistics__list server-statistics__list--show list-reset class-percent__list">
                <li class="class-percent__row">
                    <span class="class-percent__ico" style="background-image:url('<?php echo getPlayerClassAvatar(0, false, false, null); ?>');"></span>
                    <div class="class-percent__track">
                        <div class="class-percent__bar class-percent__bar--dw" style="width:<?php echo max(1, $DarkWizardPercent); ?>%;"><span class="class-percent__pct"><?php echo $DarkWizardPercent; ?>%</span></div>
                    </div>
                    <span class="class-percent__label"><span class="class-percent__num"><?php echo $DarkWizardTotal; ?></span> WIZARDS</span>
                </li>
                <li class="class-percent__row">
                    <span class="class-percent__ico" style="background-image:url('<?php echo getPlayerClassAvatar(16, false, false, null); ?>');"></span>
                    <div class="class-percent__track">
                        <div class="class-percent__bar class-percent__bar--dk" style="width:<?php echo max(1, $DarkKnightPercent); ?>%;"><span class="class-percent__pct"><?php echo $DarkKnightPercent; ?>%</span></div>
                    </div>
                    <span class="class-percent__label"><span class="class-percent__num"><?php echo $DarkKnightTotal; ?></span> KNIGHTS</span>
                </li>
                <li class="class-percent__row">
                    <span class="class-percent__ico" style="background-image:url('<?php echo getPlayerClassAvatar(32, false, false, null); ?>');"></span>
                    <div class="class-percent__track">
                        <div class="class-percent__bar class-percent__bar--elf" style="width:<?php echo max(1, $ElfPercent); ?>%;"><span class="class-percent__pct"><?php echo $ElfPercent; ?>%</span></div>
                    </div>
                    <span class="class-percent__label"><span class="class-percent__num"><?php echo $ElfTotal; ?></span> ELF</span>
                </li>
                <li class="class-percent__row">
                    <span class="class-percent__ico" style="background-image:url('<?php echo getPlayerClassAvatar(48, false, false, null); ?>');"></span>
                    <div class="class-percent__track">
                        <div class="class-percent__bar class-percent__bar--mg" style="width:<?php echo max(1, $MagicGladiatorPercent); ?>%;"><span class="class-percent__pct"><?php echo $MagicGladiatorPercent; ?>%</span></div>
                    </div>
                    <span class="class-percent__label"><span class="class-percent__num"><?php echo $MagicGladiatorTotal; ?></span> MAGIC GLADIATORS</span>
                </li>
                <li class="class-percent__row">
                    <span class="class-percent__ico" style="background-image:url('<?php echo getPlayerClassAvatar(64, false, false, null); ?>');"></span>
                    <div class="class-percent__track">
                        <div class="class-percent__bar class-percent__bar--dl" style="width:<?php echo max(1, $DarkLordPercent); ?>%;"><span class="class-percent__pct"><?php echo $DarkLordPercent; ?>%</span></div>
                    </div>
                    <span class="class-percent__label"><span class="class-percent__num"><?php echo $DarkLordTotal; ?></span> DARK LORDS</span>
                </li>
                <li class="class-percent__row">
                    <span class="class-percent__ico" style="background-image:url('<?php echo getPlayerClassAvatar(80, false, false, null); ?>');"></span>
                    <div class="class-percent__track">
                        <div class="class-percent__bar class-percent__bar--sm" style="width:<?php echo max(1, $SummonerPercent); ?>%;"><span class="class-percent__pct"><?php echo $SummonerPercent; ?>%</span></div>
                    </div>
                    <span class="class-percent__label"><span class="class-percent__num"><?php echo $SummonerTotal; ?></span> SUMMONERS</span>
                </li>
                <li class="class-percent__row">
                    <span class="class-percent__ico" style="background-image:url('<?php echo getPlayerClassAvatar(96, false, false, null); ?>');"></span>
                    <div class="class-percent__track">
                        <div class="class-percent__bar class-percent__bar--rf" style="width:<?php echo max(1, $RageFighterPercent); ?>%;"><span class="class-percent__pct"><?php echo $RageFighterPercent; ?>%</span></div>
                    </div>
                    <span class="class-percent__label"><span class="class-percent__num"><?php echo $RageFighterTotal; ?></span> RAGE FIGHTERS</span>
                </li>
            </ul>
        </div>
        <?php
            }
        }
        ?>

 <!----       <div class="main-rightbar__server-statistics server-statistics statistics">
            <span class="server-statistics__header rightbar__title statistics__title">
                <span><?php echo 'Event Time'; ?></span>
            </span>
            <ul class="server-statistics__list server-statistics__list--show statistics_card list-reset" id="stat1">
                <li class="server-statistics__item statistics__item ">
                    <div>
                        <span id="bloodcastle_name"></span><br>
                        <span>Starts In</span>
                    </div>
                    <div>
                        <span class="selection-text--light-blue" id="bloodcastle_next"></span><br>
                        <span class="selection-text--light-blue" id="bloodcastle"></span>
                    </div>
                </li>
                <li class="server-statistics__item statistics__item ">
                    <div>
                        <span id="devilsquare_name"></span><br>
                        <span>Starts In</span>
                    </div>
                    <div>
                        <span class="selection-text--light-blue" id="devilsquare_next"></span><br>
                        <span class="selection-text--light-blue" id="devilsquare"></span>
                    </div>
                </li>
                <li class="server-statistics__item statistics__item ">
                    <div>
                        <span id="chaoscastle_name"></span><br>
                        <span>Starts In</span>
                    </div>
                    <div>
                        <span class="selection-text--light-blue" id="chaoscastle_next"></span><br>
                        <span class="selection-text--light-blue" id="chaoscastle"></span>
                    </div>
                </li>
                <li class="server-statistics__item statistics__item ">
                    <div>
                        <span id="dragoninvasion_name"></span><br>
                        <span>Starts In</span>
                    </div>
                    <div>
                        <span class="selection-text--light-blue" id="dragoninvasion_next"></span><br>
                        <span class="selection-text--light-blue" id="dragoninvasion"></span>
                    </div>
                </li>
                <li class="server-statistics__item statistics__item ">
                    <div>
                        <span id="goldeninvasion_name"></span><br>
                        <span>Starts In</span>
                    </div>
                    <div>
                        <span class="selection-text--light-blue" id="goldeninvasion_next"></span><br>
                        <span class="selection-text--light-blue" id="goldeninvasion"></span>
                    </div>
                </li>
                <li class="server-statistics__item statistics__item ">
                    <div>
                        <span id="skeletonking_name"></span><br>
                        <span>Starts In</span>
                    </div>
                    <div>
                        <span class="selection-text--light-blue" id="skeletonking_next"></span><br>
                        <span class="selection-text--light-blue" id="skeletonking"></span>
                    </div>
                </li>
                <li class="server-statistics__item statistics__item ">
                    <div>
                        <span id="whitewizard_name"></span><br>
                        <span>Starts In</span>
                    </div>
                    <div>
                        <span class="selection-text--light-blue" id="whitewizard_next"></span><br>
                        <span class="selection-text--light-blue" id="whitewizard"></span>
                    </div>
                </li>
                <li class="server-statistics__item statistics__item ">
                    <div>
                        <span id="medusa_name"></span><br>
                        <span>Starts In</span>
                    </div>
                    <div>
                        <span class="selection-text--light-blue" id="medusa_next"></span><br>
                        <span class="selection-text--light-blue" id="medusa"></span>
                    </div>
                </li>
                <li class="server-statistics__item statistics__item ">
                    <div>
                        <span id="illusiontemple_name"></span><br>
                        <span>Starts In</span>
                    </div>
                    <div>
                        <span class="selection-text--light-blue" id="illusiontemple_next"></span><br>
                        <span class="selection-text--light-blue" id="illusiontemple"></span>
                    </div>
                </li>
                <li class="server-statistics__item statistics__item ">
                    <div>
                        <span id="mossevent_name"></span><br>
                        <span>Starts In</span>
                    </div>
                    <div>
                        <span class="selection-text--light-blue" id="mossevent_next"></span><br>
                        <span class="selection-text--light-blue" id="mossevent"></span>
                    </div>
                </li>
                <li class="server-statistics__item statistics__item ">
                    <div>
                        <span id="kundun_name"></span><br>
                        <span>Starts In</span>
                    </div>
                    <div>
                        <span class="selection-text--light-blue" id="kundun_next"></span><br>
                        <span class="selection-text--light-blue" id="kundun"></span>
                    </div>
                </li>
                <li class="server-statistics__item statistics__item ">
                    <div>
                        <span id="santaclaus_name"></span><br>
                        <span>Starts In</span>
                    </div>
                    <div>
                        <span class="selection-text--light-blue" id="santaclaus_next"></span><br>
                        <span class="selection-text--light-blue" id="santaclaus"></span>
                    </div>
                </li>
                <li class="server-statistics__item statistics__item ">
                    <div>
                        <span id="fortunepouch_name"></span><br>
                        <span>Starts In</span>
                    </div>
                    <div>
                        <span class="selection-text--light-blue" id="fortunepouch_next"></span><br>
                        <span class="selection-text--light-blue" id="fortunepouch"></span>
                    </div>
                </li>
                <li class="server-statistics__item statistics__item ">
                    <div>
                        <span id="fireflameghost_name"></span><br>
                        <span>Starts In</span>
                    </div>
                    <div>
                        <span class="selection-text--light-blue" id="fireflameghost_next"></span><br>
                        <span class="selection-text--light-blue" id="fireflameghost"></span>
                    </div>
                </li>
                <li class="server-statistics__item statistics__item ">
                    <div>
                        <span id="lunarrabbits_name"></span><br>
                        <span>Starts In</span>
                    </div>
                    <div>
                        <span class="selection-text--light-blue" id="lunarrabbits_next"></span><br>
                        <span class="selection-text--light-blue" id="lunarrabbits"></span>
                    </div>
                </li>
                <li class="server-statistics__item statistics__item ">
                    <div>
                        <span id="stonegolemnoria_name"></span><br>
                        <span>Starts In</span>
                    </div>
                    <div>
                        <span class="selection-text--light-blue" id="stonegolemnoria_next"></span><br>
                        <span class="selection-text--light-blue" id="stonegolemnoria"></span>
                    </div>
                </li>
                <li class="server-statistics__item statistics__item ">
                    <div>
                        <span id="dropevent_name"></span><br>
                        <span>Starts In</span>
                    </div>
                    <div>
                        <span class="selection-text--light-blue" id="dropevent_next"></span><br>
                        <span class="selection-text--light-blue" id="dropevent"></span>
                    </div>
                </li>
                <li class="server-statistics__item statistics__item ">
                    <div>
                        <span id="nightevent_name"></span><br>
                        <span>Starts In</span>
                    </div>
                    <div>
                        <span class="selection-text--light-blue" id="nightevent_next"></span><br>
                        <span class="selection-text--light-blue" id="nightevent"></span>
                    </div>
                </li>
            </ul>
        </div>---->

    </div>
</section>

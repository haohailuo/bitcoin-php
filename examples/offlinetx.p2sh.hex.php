<?php

require __DIR__ . "/../vendor/autoload.php";

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\TransactionOutput;

$ecAdapter = Bitcoin::getEcAdapter();
$math = $ecAdapter->getMath();

$privHex1 = '421c76d77563afa1914846b010bd164f395bd34c2102e5e99e0cb9cf173c1d87';
$privHex2 = 'f7225388c1d69d57e6251c9fda50cbbf9e05131e5adb81e5aa0422402f048162';
$redeemScriptHex = '52410443f3ce7c4ddf438900a6662420511ea48321f8cedd3e63943700b07ac9752a6bf18230095730b18f2d3c3dbdc0a892ca62b1722730f183d370963d6f4d3e20c84104f260c8b554e9d0921c507fb231d0e226ba17462078825c56170facb6567dcec700750bd529f4361da21f59fbfc7d0bce319fdef4e7c524e82d3e313e92b1b34752ae';
$txid = '4141414141414141414141414141414141414141414141414141414141414141';
$vout = 0;
$amount = '161662670';
$fee = '12345';
$amountAfterFee = $amount - $fee;

// Two users independently create private keys.
$pk1 = PrivateKeyFactory::fromHex($privHex1);
$pk2 = PrivateKeyFactory::fromHex($privHex2);

$outpoint = new OutPoint(Buffer::hex($txid), $vout);
$redeemScript = ScriptFactory::fromHex($redeemScriptHex);
$os = ScriptFactory::scriptPubKey()->payToScriptHash(Hash::sha256ripe160($redeemScript->getBuffer()));
$txOut = new TransactionOutput(
    $amount,
    $os
);
// One party (pk1) wants to spend funds. He creates a transaction spending the funding tx to his address.
$spendTx = TransactionFactory::build()
    ->spendOutPoint($outpoint)
    ->payToAddress($amountAfterFee, $pk1->getAddress())
    ->get();

// Two parties sign the transaction (can be done in steps)
$signer = new \BitWasp\Bitcoin\Transaction\Factory\Signer($spendTx, $ecAdapter);
$signer
    ->sign(0, $pk1, $txOut, $redeemScript)
    ->sign(0, $pk2, $txOut, $redeemScript);

$signed = $signer->get();


$fundTx = TransactionFactory::build() ->input('4d7adb19fff03892a11924f2a9188e36dbbdf0f3bb341b6514475e4df238184b', 0) ->output(1000000, $os) ->get();
echo $fundTx->getHex(). "\n";
echo $fundTx->getTxId()->getHex() . "\n";
echo "Fully signed transaction: " . $signed->getHex() . "\n";


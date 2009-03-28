foreach (35..50) {
        print "$_...\n";
        `find $_ -name "*120x120.jpg" -size +10k -delete`;
        `find $_ -name "*213x160" -size +30k -delete`;
}



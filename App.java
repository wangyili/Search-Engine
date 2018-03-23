package com.yilinwang;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileWriter;
import java.io.IOException;
import java.io.BufferedWriter;

import org.apache.tika.exception.TikaException;
import org.apache.tika.metadata.Metadata;
import org.apache.tika.parser.ParseContext;
import org.apache.tika.parser.html.HtmlParser;
import org.apache.tika.sax.BodyContentHandler;

import org.xml.sax.SAXException;

public class App 
{
    public static void main( String[] args ) throws IOException, SAXException, TikaException
    {
        final int MAX_VALUE = 2147483647;
        //File file = new File("/Users/yilinwang/IdeaProjects/mt/src/main/java/words.txt");
        File outfile = new File("/Users/yilinwang/IdeaProjects/mt/src/main/java/keywords.txt");
        String dirPath = "/Users/yilinwang/IdeaProjects/mt/src/main/java/ABCNewsDownloadData/";
        File dir = new File(dirPath);
        FileWriter fw = new FileWriter(outfile);
        BufferedWriter bw = new BufferedWriter(fw);

        for(File file: dir.listFiles()){
            BodyContentHandler handler = new BodyContentHandler(MAX_VALUE);
            Metadata metadata = new Metadata();
            FileInputStream inputstream = new FileInputStream(file);
            ParseContext pcontext = new ParseContext();
            HtmlParser htmlparser = new HtmlParser();
            htmlparser.parse(inputstream, handler, metadata, pcontext);
            bw.write(handler.toString().replaceAll("\\s+", " "));

        }
        //FileInputStream inputstream = new FileInputStream(new File("/Users/yilinwang/IdeaProjects/mt/src/main/java/0a0a2059-68fc-42a9-a8c9-2bdf09faf18c.html"));
        //ParseContext pcontext = new ParseContext();

        //HtmlParser htmlparser = new HtmlParser();
        //htmlparser.parse(inputstream, handler, metadata, pcontext);
        //bw.write(handler.toString().replaceAll("\\s+", " "));
        bw.flush();
        bw.close();

    }
}
